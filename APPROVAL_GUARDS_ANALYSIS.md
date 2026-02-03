# Approval Chain Vulnerability Analysis

## 1. Missing Hard Guards
The `EstimateController::sendToClient` and `EstimateController::markAs` (status=SENT) actions currently check `$estimate->canTransitionTo()`.
However, the `canTransitionTo()` logic in `Estimate` model is strictly state-based:
```php
case self::STATUS_APPROVED:
    return [self::STATUS_SENT, self::STATUS_DRAFT];
```
It does **NOT** verify if the `approval_chain_id` (if present) is actually fully satisfied. If a database edit or race condition set the status to `APPROVED` without completing the chain, the system would allow sending it.

## 2. UI-Only Validations
*   **Checklists**: The `ApprovalController::approve` enforces checklist completion, but this is only checked when *an approver* clicks "Approve". It is not re-verified if the system attempts to auto-transition or if an admin manually overrides status.
*   **Admin Override**: The `EstimateController::markAs` bypasses the actual approval workflow entirely. An admin can manually set status to `APPROVED` or `SENT` regardless of the assigned chain or pending approvals. This is likely a feature for emergencies, but it completely bypasses the chain.

## 3. Scenarios Bypassing Approval
1.  **Direct Admin Override**: An Admin uses `markAs('sent')`.
    *   *Result*: Estimate is sent, bypassing all `ApprovalChain` logic.
2.  **Status Manipulation via Edit**: If an estimate is `APPROVED`, and a user edits it (triggering a new version), the new version starts as `DRAFT`. If they then use `markAs` on the *Draft* version to jump to `SENT` (bypassing `WAITING_APPROVAL`), they skip the chain.
    *   *Check*: `validStatusTransitions` for `DRAFT` allows jumping to `SENT`?
        *   `Estimate.php`: `case self::STATUS_DRAFT: return [self::STATUS_WAITING_APPROVAL, self::STATUS_SENT, self::STATUS_DRAFT];`
        *   **CRITICAL FINDING**: Yes, `DRAFT` -> `SENT` is a valid transition in the model. This means **ANY** user with `update` permission (who can call `markAs`) can bypass the approval chain and send headers directly if the policy allows.

## 4. Suggested Hard Guards
1.  **Block Draft -> Sent**:
    *   Remove `STATUS_SENT` from the valid transitions for `STATUS_DRAFT` in `Estimate.php`.
    *   Require `DRAFT` -> `WAITING_APPROVAL` -> `APPROVED` -> `SENT`.
2.  **Verify Chain Completion**:
    *   In `sendToClient` (and `markAs('sent')`), explicitly check `$estimate->isFullyApproved()`.
    *   If `isFullyApproved()` is false, deny the action unless the user is Super Admin (explicit override).
3.  **Admin Override Audit**:
    *   If an Admin forces a bypass, log a specific `approval_bypassed` activity log with reasoning.

## Plan
1.  Modify `Estimate::validStatusTransitions` to remove `DRAFT` -> `SENT`.
2.  Update `EstimateController::markAs` to enforce `isFullyApproved()` check for non-super-admins when moving to `SENT`.
