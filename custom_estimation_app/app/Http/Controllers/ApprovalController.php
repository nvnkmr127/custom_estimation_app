<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
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
            $estimate->submitForApproval();

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

            if (! $approval) {
                return redirect()->back()->with('error', 'You do not have permission to approve this estimate.');
            }

            // Update approval status
            $approval->update([
                'status' => 'approved',
                'comments' => $request->input('comments'),
            ]);

            // Check if all approvals are complete
            if ($estimate->isFullyApproved()) {
                $estimate->update([
                    'approval_status' => 'approved',
                    'status' => 'approved',
                ]);
            } else {
                // Create approval for next step
                $nextStep = $estimate->nextApprovalStep();
                if ($nextStep) {
                    \App\Models\EstimateApproval::create([
                        'estimate_id' => $estimate->id,
                        'user_id' => $nextStep->user_id,
                        'status' => 'pending',
                    ]);
                }
            }

            return redirect()->back()->with('success', 'Estimate approved successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Estimate Approval Failed', [
                'estimate_id' => $estimate->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Approval failed: '.$e->getMessage());
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

            if (! $approval) {
                return redirect()->back()->with('error', 'You do not have permission to reject this estimate.');
            }

            // Update approval status
            $approval->update([
                'status' => 'rejected',
                'comments' => $request->input('comments', 'Rejected'),
            ]);

            // Update estimate status
            $estimate->update(['approval_status' => 'rejected']);

            return redirect()->back()->with('success', 'Estimate rejected.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Estimate Rejection Failed', [
                'estimate_id' => $estimate->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Rejection failed: '.$e->getMessage());
        }
    }
}
