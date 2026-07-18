<?php

namespace App\Console\Commands;

use App\Models\Estimate;
use App\Models\EstimateApproval;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckApprovalTimeouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approval:check-timeouts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for pending approvals that have exceeded their timeout';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pendingApprovals = EstimateApproval::where('status', 'pending')
            ->with(['estimate.approvalChain.steps', 'user'])
            ->get();

        foreach ($pendingApprovals as $approval) {
            $this->processApproval($approval);
        }

        $this->info('Checked ' . $pendingApprovals->count() . ' pending approvals.');
    }

    protected function processApproval(EstimateApproval $approval)
    {
        $estimate = $approval->estimate;
        if (!$estimate || !$estimate->approvalChain) {
            return;
        }

        // Identify the step for this approval
        $step = $this->findStepForApproval($approval, $estimate);

        if (!$step || !$step->timeout_hours) {
            return;
        }

        $createdAt = $approval->created_at;
        $timeoutTime = $createdAt->copy()->addHours($step->timeout_hours);

        if (now()->greaterThan($timeoutTime)) {
            $this->executeTimeoutAction($approval, $step, $estimate);
        }
    }

    protected function findStepForApproval($approval, $estimate)
    {
        $chain = $estimate->approvalChain;
        $steps = $chain->steps;
        $fallbackId = $chain->fallback_user_id;

        // Strategy: Find steps that match the user.
        // If approval user is fallback, find steps with deleted users.

        $candidates = $steps->filter(function ($step) use ($approval, $fallbackId) {
            if ($step->user_id == $approval->user_id) {
                return true;
            }

            // Check fallback case
            if ($approval->user_id == $fallbackId) {
                // Check if step user is deleted
                $stepUser = \App\Models\User::withTrashed()->find($step->user_id);
                if ($stepUser && $stepUser->trashed()) {
                    return true;
                }
            }

            return false;
        });

        // If multiple candidates, filter by current approval "wave"
        // We assume all pending approvals for an estimate belong to the same order.
        // So we can find the order that has this user.
        // But what if same user is in multiple steps?
        // We take the FIRST one that is logically "next" or "current".
        // Since we don't store order in approval, we rely on the candiate order.
        // It's likely the lowest order candidate that hasn't been passed?
        // Actually, if we are pending, we are AT that order.
        // So just return the first match.

        return $candidates->first();
    }

    protected function executeTimeoutAction($approval, $step, $estimate)
    {
        $action = $step->timeout_action ?? 'notify';

        Log::info("Approval timeout action triggered: {$action}", [
            'approval_id' => $approval->id,
            'estimate_id' => $estimate->id,
            'user_id' => $approval->user_id
        ]);

        switch ($action) {
            case 'reject':
                $this->rejectEstimate($approval, $estimate);
                break;
            case 'skip':
                $this->skipApproval($approval, $estimate);
                break;
            case 'notify':
            default:
                $this->notifyApprover($approval);
                break;
        }
    }

    protected function rejectEstimate($approval, $estimate)
    {
        $approval->update([
            'status' => 'rejected',
            'comments' => 'Auto-rejected due to timeout.',
        ]);

        $estimate->update(['approval_status' => 'rejected']);
    }

    protected function skipApproval($approval, $estimate)
    {
        $approval->update([
            'status' => 'approved',
            'comments' => 'Auto-skipped due to timeout.',
        ]);

        // Check for next steps
        // This duplicates logic in ApprovalController::approve
        // We should really extract this logic.

        // Copied Logic:
        $hasPending = $estimate->approvals()->where('status', 'pending')->exists();

        if (!$hasPending) {
            if ($estimate->isFullyApproved()) {
                $estimate->update([
                    'approval_status' => 'approved',
                    'status' => 'approved',
                ]);
            } else {
                $nextSteps = $estimate->nextApprovalSteps();
                if ($nextSteps->isNotEmpty()) {
                    $order = $nextSteps->first()->order;
                    $estimate->createApprovalsForOrder($order);
                }
            }
        }
    }

    protected function notifyApprover($approval)
    {
        Log::info("Sending timeout notification to user {$approval->user_id} for estimate {$approval->estimate_id}");

        $approver = $approval->user;
        if ($approver) {
            $approver->notify(new \App\Notifications\ApprovalTimeoutNotification($approval));
        }
    }
}
