<?php

namespace App\Services\Estimates;

use App\Models\Estimate;
use App\Models\EstimateApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstimateWorkflowService
{
    protected EstimateStateService $stateService;
    protected ApprovalChainEvaluator $evaluator;
    protected \App\Core\Events\EventDispatcherInterface $dispatcher;

    public function __construct(
        EstimateStateService $stateService,
        ApprovalChainEvaluator $evaluator,
        \App\Core\Events\EventDispatcherInterface $dispatcher
    ) {
        $this->stateService = $stateService;
        $this->evaluator = $evaluator;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Submit an estimate for internal approval.
     */
    public function submitForApproval(Estimate $estimate): Estimate
    {
        // 1. Validate State
        if (!in_array($estimate->approval_status, [Estimate::APP_STATUS_DRAFT, Estimate::APP_STATUS_CHANGES_REQUESTED])) {
            throw new \Exception("Only draft or changes requested estimates can be submitted for approval.");
        }

        // 2. Validate Completeness (Must have items)
        if ($estimate->items()->count() === 0 && $estimate->sections()->count() === 0) {
            throw new \Exception("Cannot submit an empty estimate. Please add some items first.");
        }

        return DB::transaction(function () use ($estimate) {
            // Re-lock the estimate record
            $estimate = Estimate::where('id', $estimate->id)->lockForUpdate()->firstOrFail();

            // 3. Evaluate Approval Chain
            $chain = $this->evaluator->evaluate($estimate);

            if (!$chain) {
                // AUTO-APPROVE
                $this->stateService->transitionEstimateStatus($estimate, Estimate::EST_STATUS_ACTIVE);
                $this->stateService->transitionApprovalStatus($estimate, Estimate::APP_STATUS_APPROVED);

                // Sync legacy status
                $estimate->update(['status' => Estimate::STATUS_APPROVED]);

                // Dispatch Domain Event
                $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateApproved($estimate, auth()->id() ?? 0, 'internal'));

                return $estimate;
            }

            // 4. Update Chain Association
            $estimate->update(['approval_chain_id' => $chain->id]);

            // 5. Transition to Submitted & Active (Locked)
            $this->stateService->transitionEstimateStatus($estimate, Estimate::EST_STATUS_ACTIVE);
            $this->stateService->transitionApprovalStatus($estimate, Estimate::APP_STATUS_SUBMITTED);

            // 6. Generate First Step Approvals
            $firstStepOrder = $chain->steps()->min('order');
            if ($firstStepOrder !== null) {
                $estimate->createApprovalsForOrder($firstStepOrder);
            }

            // 7. Transition to Pending
            $this->stateService->transitionApprovalStatus($estimate, Estimate::APP_STATUS_PENDING);

            // 8. Dispatch Domain Event (Atomic via transaction)
            $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateSubmittedForApproval($estimate, auth()->id()));

            return $estimate;
        });
    }

    /**
     * Approve an estimate (or a step in the process).
     */
    public function approve(Estimate $estimate, int $userId, ?string $comments = null): Estimate
    {
        return DB::transaction(function () use ($estimate, $userId, $comments) {
            $estimate = Estimate::where('id', $estimate->id)->lockForUpdate()->firstOrFail();

            $approval = $estimate->approvals()
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                throw new \Exception("No pending approval found for this user.");
            }

            $approval->update([
                'status' => 'approved',
                'comments' => $comments,
            ]);

            // PARALLEL LOGIC: Determine if we should move to the next step
            $shouldAdvance = false;

            // Find the specific step definition for this user in this chain
            $approvingStep = $estimate->approvalChain ? $estimate->approvalChain->steps()
                ->where('user_id', $userId)
                ->first() : null;

            if ($approvingStep && !$approvingStep->require_all) {
                // RULE: REQUIRE ANY - First approval completes the step
                $shouldAdvance = true;

                // Identify and clear other pending approvals at the same order level
                $peerUserIds = $estimate->approvalChain->steps()
                    ->where('order', $approvingStep->order)
                    ->where('user_id', '!=', $userId)
                    ->pluck('user_id');

                if ($peerUserIds->isNotEmpty()) {
                    $estimate->approvals()
                        ->whereIn('user_id', $peerUserIds)
                        ->where('status', 'pending')
                        ->delete();
                }
            } else {
                // RULE: REQUIRE ALL (Default) - Wait for everyone at the current level
                $hasOtherPending = $estimate->approvals()
                    ->where('status', 'pending')
                    ->exists();

                if (!$hasOtherPending) {
                    $shouldAdvance = true;
                }
            }

            if ($shouldAdvance) {
                // Time to check for next step or final approval
                $nextSteps = $estimate->nextApprovalSteps();
                if ($nextSteps->isNotEmpty()) {
                    $order = $nextSteps->first()->order;
                    $estimate->createApprovalsForOrder($order);
                } else {
                    // Final Approval reached
                    $this->stateService->transitionApprovalStatus($estimate, Estimate::APP_STATUS_APPROVED);

                    // 10. Dispatch Domain Event
                    $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateApproved($estimate, $userId, 'internal'));

                    // Sync legacy status
                    $estimate->update(['status' => Estimate::STATUS_APPROVED]);
                }
            }

            return $estimate;
        });
    }

    /**
     * Reject an estimate, locking it permanently in VOID state.
     */
    public function reject(Estimate $estimate, int $userId, string $comments): Estimate
    {
        return DB::transaction(function () use ($estimate, $userId, $comments) {
            $estimate = Estimate::where('id', $estimate->id)->lockForUpdate()->firstOrFail();

            $approval = $estimate->approvals()
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                throw new \Exception("No pending approval found for this user.");
            }

            $approval->update([
                'status' => 'rejected',
                'comments' => $comments,
            ]);

            // Clear all other pending approvals for this estimate
            $estimate->approvals()->where('status', 'pending')->delete();

            $this->stateService->transitionApprovalStatus($estimate, Estimate::APP_STATUS_REJECTED);
            $this->stateService->transitionEstimateStatus($estimate, Estimate::EST_STATUS_VOID);

            return $estimate;
        });
    }

    /**
     * Request changes, sending the estimate back to DRAFT for editing.
     */
    public function requestChanges(Estimate $estimate, int $userId, string $comments): Estimate
    {
        return DB::transaction(function () use ($estimate, $userId, $comments) {
            $estimate = Estimate::where('id', $estimate->id)->lockForUpdate()->firstOrFail();

            $approval = $estimate->approvals()
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                throw new \Exception("No pending approval found for this user.");
            }

            $approval->update([
                'status' => 'changes_requested',
                'comments' => $comments,
            ]);

            // Clear all approval history so the workflow starts fresh on re-submission
            $estimate->approvals()->delete();

            $this->stateService->transitionApprovalStatus($estimate, Estimate::APP_STATUS_CHANGES_REQUESTED);
            $this->stateService->transitionEstimateStatus($estimate, Estimate::EST_STATUS_DRAFT);

            return $estimate;
        });
    }
}
