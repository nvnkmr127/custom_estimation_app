# Estimate Lifecycle Trace (Current State)

This document outlines the complete lifecycle of an Estimate, from creation to client acceptance, as currently implemented.

## 1. Creation Phase (Internal)

**Actor:** Estimator / Admin
**Entry Point:** `EstimateController::store()` -> `EstimateService::createEstimate()`

1.  **Initialization**:
    *   **Number Generation**: Atomic generation of `estimate_number` (e.g., `EST-2024-001`) using `App\Models\Setting` DB locks to prevent duplicates.
    *   **Status**: Defaults to `DRAFT`.
    *   **Data**: Sections and Items are created.
2.  **Calculation**:
    *   Subtotals, taxes, and discounts are computed.
    *   **Chain Assignment**: `ApprovalChain` assigned based on priority (Discount % > Amount).
3.  **Output**: Estimate saved, `EstimateCreated` event dispatched.

## 2. Refinement & Versioning

**Actor:** Estimator
**Logic:** `EstimateService::updateEstimate()`

*   **Editing**:
    *   **Forced Branching**: If the estimate is `SENT`, `APPROVED`, etc., editing forces a **New Version**.
    *   **Auto-Expiry of Old Version**: **[SECURE]** When a new version is created, if the previous version was `SENT`, it is automatically set to `EXPIRED`. This prevents clients from accepting outdated proposals.
*   **Recalculation**: Totals and Approval Chains are re-evaluated on every save.

## 3. Approval Workflow

**Actor:** Estimator -> Approvers
**Entry Point:** `ApprovalController::submit()`

1.  **Submission**:
    *   **Auto-Approval**: If no chain applies, status -> `APPROVED`.
    *   **Review**: Status -> `WAITING_APPROVAL`. Approvals created for first step.
2.  **Review Process**:
    *   **Approve**: Locks DB row. Checks checklists. If complete, status -> `APPROVED`.
    *   **Reject/Request Changes**: Status reverts to `DRAFT`.

## 4. Client Delivery

**Actor:** System / Estimator
**Entry Point:** `EstimateController::sendToClient()`

*   **Logic**: Sends public signed URL. Updates status to `SENT`.

## 5. Client Interaction (Portal)

**Actor:** Client
**Entry Point:** `PortalController`

1.  **Viewing**: Access via Signed URL.
2.  **Acceptance (`accept`)**: **[SECURE]**
    *   **Concurrency**: Uses `DB::transaction()` with `lockForUpdate()`.
    *   **Validation**: explicitly checks `$estimate->canTransitionTo('accepted')`.
    *   **Outcome**: Updates status, captures IP/Location, Syncs to CRM, Notifies Admins.
3.  **Negection (`decline`)**: **[SECURE]**
    *   **Concurrency**: Uses `DB::transaction()` with `lockForUpdate()`.
    *   **Validation**: explicitly checks `$estimate->canTransitionTo('declined')`.
    *   **Outcome**: Updates status to `DECLINED`, captures notes.

---

## 🔒 Security & Validation Checkpoints

### Resolved Risks
The following risks have been identified and **mitigated** in the current implementation:

1.  **Race Conditions in Portal**:
    *   *Previously*: Concurrent requests could accept an estimate twice or during an admin update.
    *   *Fix*: Implemented `DB::transaction` and `lockForUpdate` in `PortalController`.

2.  **Invalid State Transitions**:
    *   *Previously*: A signed URL allowed accepting `EXPIRED` or `DECLINED` estimates.
    *   *Fix*: Explicit `canTransitionTo()` check guards all Portal actions.

3.  **Stale Version Acceptance**:
    *   *Previously*: Old versions remained `SENT` and accessible.
    *   *Fix*: Creating a new version now immediately marks the parent as `EXPIRED` if it was sent.

### Remaining Constraints (By Design)
*   **Approval Chain Mutation**: Assigning a chain happens at calculation/save time. If global rules change *while* an estimate is pending, it adheres to the chain assigned at creation (snapshot behavior), which is standard for auditability.
