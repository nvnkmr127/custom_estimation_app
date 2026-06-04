<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    protected $dispatcher;
    protected $workflowService;

    public function __construct(
        \App\Core\Events\EventDispatcherInterface $dispatcher,
        \App\Services\Estimates\EstimateWorkflowService $workflowService
    ) {
        $this->dispatcher = $dispatcher;
        $this->workflowService = $workflowService;
    }
    /**
     * Display a listing of estimates waiting for approval by current user.
     */
    public function index()
    {
        $user = auth()->user();

        // Get estimates where user has a pending approval
        $pendingEstimates = Estimate::where('approval_status', Estimate::APP_STATUS_WAITING)
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
        $this->authorize('update', $estimate);

        try {
            \Log::info("ApprovalController@submit called for Estimate ID: {$estimate->id} by User ID: " . auth()->id());

            $estimate = $this->workflowService->submitForApproval($estimate);

            \Log::info("ApprovalChain evaluation complete. Estimate status is now: {$estimate->approval_status}");

            if ($estimate->approval_status === Estimate::APP_STATUS_APPROVED) {
                return redirect()->back()->with('success', 'Estimate approved automatically as it does not require additional authorization.');
            }

            return redirect()->back()->with('success', 'Estimate submitted for approval successfully.');
        } catch (\Exception $e) {
            \Log::error("ApprovalController@submit failed for Estimate ID: {$estimate->id} - Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve an estimate
     */
    public function approve(Request $request, Estimate $estimate)
    {
        $policy = app(\App\Services\Estimates\EstimateStateService::class)->getPolicy($estimate);
        if (!($policy['can_approve'] ?? false)) {
            abort(403, 'Unauthorized.');
        }

        try {
            $user = auth()->user();
            $estimate = $this->workflowService->approve($estimate, $user->id, $request->input('comments'));

            return redirect()->back()->with('success', 'Estimate approved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject an estimate
     */
    public function reject(Request $request, Estimate $estimate)
    {
        $policy = app(\App\Services\Estimates\EstimateStateService::class)->getPolicy($estimate);
        if (!($policy['can_approve'] ?? false)) {
            abort(403, 'Unauthorized.');
        }

        try {
            $user = auth()->user();

            $request->validate([
                'comments' => 'required|string|min:5|max:1000',
            ]);

            $comments = $request->input('comments');

            $estimate = $this->workflowService->reject($estimate, $user->id, $comments);

            return redirect()->back()->with('success', 'Estimate rejected successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Rejection failed: ' . $e->getMessage());
        }
    }

    /**
     * Request changes on an estimate
     */
    public function requestChanges(Request $request, Estimate $estimate)
    {
        $policy = app(\App\Services\Estimates\EstimateStateService::class)->getPolicy($estimate);
        if (!($policy['can_approve'] ?? false)) {
            abort(403, 'Unauthorized.');
        }

        try {
            $user = auth()->user();
            $validated = $request->validate([
                'comments' => 'required|string|min:5',
            ]);

            $estimate = $this->workflowService->requestChanges($estimate, $user->id, $validated['comments']);

            return redirect()->back()->with('success', 'Changes requested successfully. The estimate has been reverted to draft.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Requesting changes failed: ' . $e->getMessage());
        }
    }

    /**
     * Toggle checklist item status via AJAX
     */
    public function toggleChecklistItem(Request $request, Estimate $estimate)
    {
        try {
            $policy = app(\App\Services\Estimates\EstimateStateService::class)->getPolicy($estimate);
            if (!($policy['can_approve'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to toggle checklist items for this estimate.'
                ], 403);
            }

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
