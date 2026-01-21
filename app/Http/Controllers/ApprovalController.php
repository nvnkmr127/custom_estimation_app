<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    protected $dispatcher;

    public function __construct(\App\Core\Events\EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    /**
     * Display a listing of estimates waiting for approval by current user.
     */
    public function index()
    {
        $user = auth()->user();

        // Get estimates where user has a pending approval
        $pendingEstimates = Estimate::where('approval_status', 'submitted')
            ->whereHas('approvals', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', 'pending');
            })
            ->with(['approvalChain.steps', 'approvals.user'])
            ->latest()
            ->paginate(10);

        return view('approvals.index', compact('pendingEstimates'));
    }

    /**
     * Submit estimate for approval workflow
     */
    public function submit(Estimate $estimate)
    {
        try {
            $createdApprovals = $estimate->submitForApproval();

            // Dispatch events
            foreach ($createdApprovals as $approval) {
                $this->dispatcher->dispatch(new \App\Core\Events\Approvals\ApprovalRequested(
                    $estimate->id,
                    $approval->id,
                    $approval->user_id
                ));
            }

            return redirect()->back()->with('success', 'Estimate submitted for approval successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve an estimate
     */
    public function approve(Request $request, Estimate $estimate)
    {
        try {
            $user = auth()->user();

            // Find pending approval for this user
            $approval = $estimate->approvals()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                return redirect()->back()->with('error', 'You do not have permission to approve this estimate.');
            }

            // Check if all mandatory checklist items are completed
            $requiredChecklists = \App\Models\ApprovalChecklist::where('is_required', true)->get();
            $completedItems = $estimate->checklistItems()->where('is_completed', true)->pluck('approval_checklist_id')->toArray();

            foreach ($requiredChecklists as $checklist) {
                if (!in_array($checklist->id, $completedItems)) {
                    return redirect()->back()->with('error', 'You must complete the mandatory checklist items before approving.');
                }
            }

            // Update approval status
            $approval->update([
                'status' => 'approved',
                'comments' => $request->input('comments'),
            ]);

            // Dispatch Event
            $this->dispatcher->dispatch(new \App\Core\Events\Approvals\ApprovalApproved(
                $estimate->id,
                $approval->id,
                $user->id
            ));

            // Check if there are other pending approvals for this stage
            // We can check strictly by order, but checking global pending status is safer
            // provided the next steps haven't been created yet.
            $hasPending = $estimate->approvals()->where('status', 'pending')->exists();

            if (!$hasPending) {
                // If all approvals for this stage are done, check what's next
                // Check if all approvals are complete
                if ($estimate->isFullyApproved()) {
                    $estimate->update([
                        'approval_status' => 'approved',
                        'status' => 'approved',
                    ]);
                    // Dispatch EstimateApproved if needed via internal flow?
                    // "EstimateApproved" (Internal)
                    $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateApproved($estimate, $user->id, 'internal'));

                } else {
                    // Create approval for next step(s)
                    $nextSteps = $estimate->nextApprovalSteps();
                    if ($nextSteps->isNotEmpty()) {
                        $order = $nextSteps->first()->order;
                        $newApprovals = $estimate->createApprovalsForOrder($order);

                        // Dispatch events for next steps
                        foreach ($newApprovals as $newApproval) {
                            $this->dispatcher->dispatch(new \App\Core\Events\Approvals\ApprovalRequested(
                                $estimate->id,
                                $newApproval->id,
                                $newApproval->user_id
                            ));
                        }
                    }
                }
            }

            return redirect()->back()->with('success', 'Estimate approved successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Estimate Approval Failed', [
                'estimate_id' => $estimate->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    /**
     * Reject an estimate
     */
    public function reject(Request $request, Estimate $estimate)
    {
        try {
            $user = auth()->user();

            // Find pending approval for this user
            $approval = $estimate->approvals()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                return redirect()->back()->with('error', 'You do not have permission to reject this estimate.');
            }

            // Update approval status
            $approval->update([
                'status' => 'rejected',
                'comments' => $request->input('comments', 'Rejected'),
            ]);

            // Dispatch Event
            $this->dispatcher->dispatch(new \App\Core\Events\Approvals\ApprovalRejected(
                $estimate->id,
                $approval->id,
                $user->id,
                $request->input('comments', 'Rejected')
            ));

            // Update estimate status
            $estimate->update(['approval_status' => 'rejected']);

            // Dispatch EstimateRejected (Internal)
            $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateRejected($estimate->id, $user->id, $request->input('comments')));

            return redirect()->back()->with('success', 'Estimate rejected.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Estimate Rejection Failed', [
                'estimate_id' => $estimate->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Rejection failed: ' . $e->getMessage());
        }
    }

    /**
     * Request changes on an estimate
     */
    public function requestChanges(Request $request, Estimate $estimate)
    {
        try {
            $user = auth()->user();

            // Find pending approval for this user
            $approval = $estimate->approvals()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                return redirect()->back()->with('error', 'You do not have permission to request changes on this estimate.');
            }

            $validated = $request->validate([
                'comments' => 'required|string|min:5',
            ]);

            // Update approval status
            $approval->update([
                'status' => 'changes_requested',
                'comments' => $validated['comments'],
            ]);

            // Dispatch Event
            $this->dispatcher->dispatch(new \App\Core\Events\Approvals\ApprovalChangeRequested(
                $estimate->id,
                $approval->id,
                $user->id,
                $validated['comments']
            ));

            // Update estimate status to draft so it can be edited
            $estimate->update([
                'approval_status' => 'draft', // Reset approval status
                'status' => 'draft',          // Reset main status
            ]);

            // Dispatch EstimateChangeRequested (Internal)
            // $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateChangeRequested($estimate->id, $user->id, $validated['comments']));

            return redirect()->back()->with('success', 'Changes requested successfully. The estimate has been reverted to draft.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Estimate Change Request Failed', [
                'estimate_id' => $estimate->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Requesting changes failed: ' . $e->getMessage());
        }
    }

    /**
     * Toggle checklist item status via AJAX
     */
    public function toggleChecklistItem(Request $request, Estimate $estimate)
    {
        try {
            $request->validate([
                'checklist_id' => 'required|exists:approval_checklists,id',
                'completed' => 'required|boolean',
            ]);

            $checklistId = $request->input('checklist_id');
            $completed = $request->input('completed');

            $item = \App\Models\EstimateApprovalChecklistItem::updateOrCreate(
                [
                    'estimate_id' => $estimate->id,
                    'approval_checklist_id' => $checklistId,
                ],
                [
                    'is_completed' => $completed,
                    'completed_by' => auth()->id(),
                    'completed_at' => $completed ? now() : null,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Checklist updated.',
                'item' => $item
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
