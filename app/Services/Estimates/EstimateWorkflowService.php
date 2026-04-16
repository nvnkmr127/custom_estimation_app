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
        // estimate_status must be 'draft' AND approval_status must be 'not_required' or 'changes_requested'
        if ($estimate->estimate_status !== Estimate::EST_STATUS_DRAFT) {
            throw new \Exception("Only estimates in draft status can be submitted for approval (current: {$estimate->estimate_status}).");
        }

        if (!in_array($estimate->approval_status, [Estimate::APP_STATUS_NOT_REQUIRED, Estimate::APP_STATUS_CHANGES_REQUESTED])) {
            throw new \Exception("Estimate approval workflow is already active or completed (approval_status: {$estimate->approval_status}). Cannot re-submit.");
        }

        // 2. Validate Completeness (Must have items)
        $hasItems = $estimate->items()->exists();
        if (!$hasItems) {
            foreach ($estimate->sections as $section) {
                if ($section->items()->exists()) {
                    $hasItems = true;
                    break;
                }
            }
        }

        if (!$hasItems) {
            throw new \Exception("Cannot submit an empty estimate. Please add some items first.");
        }

        return DB::transaction(function () use ($estimate) {
            // Re-lock the estimate record
            $estimate = Estimate::where('id', $estimate->id)->lockForUpdate()->firstOrFail();

            // 3. Evaluate Approval Chain
            $chain = $this->evaluator->evaluate($estimate);

            if (!$chain) {
                // AUTO-APPROVE
                $this->stateService->transitionEstimateStatus($estimate, Estimate::EST_STATUS_APPROVED);
                $this->stateService->transitionApprovalStatus($estimate, Estimate::APP_STATUS_APPROVED);

                // Dispatch Domain Event
                $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateApproved($estimate, auth()->id() ?? 0, 'internal'));

                return $estimate;
            }

            // 4. Update Chain Association
            $estimate->update(['approval_chain_id' => $chain->id]);

            // 5. Transition to Submitted & Active (Locked)
            $this->stateService->transitionEstimateStatus($estimate, Estimate::EST_STATUS_PENDING_APPROVAL);
            $this->stateService->transitionApprovalStatus($estimate, Estimate::APP_STATUS_WAITING);

            // 6. Generate First Step Approvals
            $firstStepOrder = $chain->steps()->min('order');
            if ($firstStepOrder !== null) {
                $estimate->createApprovalsForOrder($firstStepOrder);
            }

            // 8. Dispatch Domain Event (Atomic via transaction)
            $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateSubmittedForApproval($estimate, auth()->id()));

            // Dispatch individual approval requests
            foreach ($estimate->approvals()->where('status', 'pending')->get() as $approval) {
                $this->dispatcher->dispatch(new \App\Core\Events\Approvals\ApprovalRequested(
                    $estimate->id,
                    $approval,
                    $approval->user_id
                ));
            }

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

            $requiredChecklistIds = \App\Models\ApprovalChecklist::where('is_required', true)->pluck('id');
            if ($requiredChecklistIds->isNotEmpty()) {
                $completedRequiredCount = \App\Models\EstimateApprovalChecklistItem::where('estimate_id', $estimate->id)
                    ->whereIn('approval_checklist_id', $requiredChecklistIds)
                    ->where('is_completed', true)
                    ->distinct('approval_checklist_id')
                    ->count('approval_checklist_id');

                if ($completedRequiredCount !== $requiredChecklistIds->count()) {
                    throw new \Exception('You must complete the mandatory checklist items before approving.');
                }
            }

            $approval = $estimate->approvals()
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->orderBy('order')
                ->first();

            $isAdmin = \App\Models\User::find($userId)?->hasRole(['super_admin', 'admin', 'estimator_admin']);
            
            if (!$approval && !$isAdmin) {
                // Check if it was already approved/acted upon to provide better feedback
                $alreadyActed = $estimate->approvals()->where('user_id', $userId)->exists();
                if ($alreadyActed) {
                    throw new \Exception("You have already acted on this estimate or it has moved to the next stage. Please refresh the page.");
                }
                throw new \Exception("You are not currently authorized to approve this estimate or it is not your turn in the approval chain.");
            }

            if ($approval) {
                $approval->update([
                    'status' => 'approved',
                    'comments' => $comments,
                ]);
            } elseif ($isAdmin) {
                // Force Approve: Identify current active order to track where the admin intervened
                $currentOrder = $estimate->approvals()->where('status', 'pending')->max('order')
                    ?? ($estimate->approvalChain ? $estimate->approvalChain->steps()->min('order') : 0);

                $approval = $estimate->approvals()->create([
                    'user_id' => $userId,
                    'status' => 'approved',
                    'order' => $currentOrder,
                    'comments' => $comments . ' (Force Approved by Admin)',
                ]);

                // When an admin forces approval, we clear all other pending requests for the entire estimate
                $estimate->approvals()->where('status', 'pending')->delete();
            }

            if ($approval) {
                $this->dispatcher->dispatch(new \App\Core\Events\Approvals\ApprovalApproved(
                    $estimate->id,
                    $approval,
                    $userId
                ));
            }

            // PARALLEL LOGIC: Determine if we should move to the next step
            $shouldAdvance = false;

            // Find the specific step definition linked to this approval record
            $approvingStep = $approval && $approval->approval_chain_step_id
                ? \App\Models\ApprovalChainStep::find($approval->approval_chain_step_id)
                : null;

            if ($isAdmin && !$approvingStep) {
                // Admin force-approved without a specific step context
                $shouldAdvance = true;
            } elseif ($approvingStep && !$approvingStep->require_all) {
                // RULE: REQUIRE ANY - First approval completes the step/order
                $shouldAdvance = true;

                // Clear other pending approvals at the same order level
                $estimate->approvals()
                    ->where('order', $approvingStep->order)
                    ->where('status', 'pending')
                    ->delete();
            } else {
                // RULE: REQUIRE ALL (Default) - Wait for everyone at the current level
                $hasOtherPendingForLevel = $estimate->approvals()
                    ->where('status', 'pending')
                    ->exists();

                if (!$hasOtherPendingForLevel) {
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
                    $this->stateService->transitionEstimateStatus($estimate, Estimate::EST_STATUS_APPROVED);

                    // 10. Dispatch Domain Event
                    $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateApproved($estimate, $userId, 'internal'));
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
                $alreadyActed = $estimate->approvals()->where('user_id', $userId)->exists();
                if ($alreadyActed) {
                    throw new \Exception("You have already acted on this estimate. Please refresh.");
                }
                throw new \Exception("No pending approval found for you. You may not be the current approver.");
            }

            $approval->update([
                'status' => 'rejected',
                'comments' => $comments,
            ]);

            // Clear all other pending approvals for this estimate
            $estimate->approvals()->where('status', 'pending')->delete();

            $this->stateService->transitionApprovalStatus($estimate, Estimate::APP_STATUS_REJECTED);
            $this->stateService->transitionEstimateStatus($estimate, Estimate::EST_STATUS_DECLINED);

            // Dispatch Domain Event
            $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateRejected($estimate, $userId, $comments));

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

            // Dispatch Domain Event
            $this->dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateRejected($estimate, $userId, 'Changes Requested: ' . $comments));

            return $estimate;
        });
    }
}
