# Estimate Lifecycle Trace

This document outlines the complete lifecycle of an Estimate, from creation to client acceptance, based on the codebase analysis.

## 1. Creation Phase (Internal)

**Actor:** Estimator / Admin
**Entry Point:** `EstimateController::store()` -> `EstimateService::createEstimate()`

1.  **Initialization**:
    *   **Number Generation**: Atomic generation of `estimate_number` (e.g., `EST-2024-001`) using `App\Models\Setting` locks to prevent duplicates.
    *   **Status**: Defaults to `DRAFT`.
    *   **Data**: Sections and Items are created.
2.  **Calculation (`recalculateTotals`)**:
    *   Subtotals, taxes, and discounts are computed.
    *   **Chain Assignment**: An `ApprovalChain` is automatically assigned based on:
        1.  **Discount %** (Highest priority).
        2.  **Total Amount** (Secondary priority).
3.  **Output**: Estimate is saved, `EstimateCreated` event dispatched.

## 2. Refinement & Versioning

**Actor:** Estimator
**Logic:** `EstimateService::updateEstimate()`

*   **Editing**:
    *   If the estimate is `DRAFT` and viewed by the **Creator** (or Admin), it updates in place.
    *   **Forced Branching**: If the estimate is `SENT`, `APPROVED`, `ACCEPTED`, or if a *non-creator* attempts to edit, a **New Version** is created automatically.
        *   Old Version: `is_current_version = false`.
        *   New Version: `is_current_version = true`, Status resets to `DRAFT`.
*   **Recalculation**: Every save triggers `recalculateTotals`, heavily potentially changing the assigned `ApprovalChain`.

## 3. Approval Workflow

**Actor:** Estimator (Submitter) -> Approvers
**Entry Point:** `ApprovalController::submit()` -> `Estimate::submitForApproval()`

1.  **Submission**:
    *   User clicks "Submit".
    *   System checks for assigned `ApprovalChain`.
    *   **Auto-Approval**: If no chain is assigned (e.g., low value), status immediately jumps to `APPROVED`.
    *   **Review Required**:
        *   Status becomes `WAITING_APPROVAL`.
        *   `EstimateApproval` records are created for the **First Step** (lowest order) of the chain.
        *   `ApprovalRequested` notifications sent to approvers.
2.  **Review Process (`ApprovalController`)**:
    *   **Approve**:
        *   Approver must complete strictly required `ApprovalChecklist` items.
        *   Mark specific `EstimateApproval` as `approved`.
        *   **Check Next**: System checks `isFullyApproved()`.
            *   *If Pending Steps*: Creates approvals for the next order sequence.
            *   *If Complete*: Status becomes `APPROVED`. `EstimateApproved` event dispatched.
    *   **Reject**:
        *   Status becomes `DRAFT` (logic in controller implies rejection/change request flow).
        *   `EstimateRejected` event dispatched.
    *   **Request Changes**:
        *   Status reverts to `DRAFT`.
        *   Creator notified to make edits (which creates a new version if finalized, or edits draft).

## 4. Client Delivery

**Actor:** System / Estimator
**Entry Point:** `EstimateController::sendToClient()`

*   **Prerequisite**: Status must be `APPROVED` (or already `SENT`).
*   **Action**:
    *   Emails the **Public Signed URL** (`portal.show`).
    *   Status updates to `SENT`.
    *   `EstimateSent` event dispatched.

## 5. Client Interaction (Portal)

**Actor:** Client
**Entry Point:** `PortalController`

1.  **Viewing (`show`)**:
    *   Access via Signed URL.
    *   Checks validation: Status must be `SENT`, `ACCEPTED`, `DECLINED`, or `EXPIRED`.
    *   Logs view (Analytics & Activity Log).
2.  **Acceptance (`accept`)**:
    *   **Action**: Client signs (text signature) and submits.
    *   **Updates**:
        *   Status -> `ACCEPTED`.
        *   Captures IP, User Agent, Location.
    *   **Sync**: Triggers `SyncEstimateToPerfex` job.
    *   **Notification**: Admins notified.
3.  **Decline (`decline`)**:
    *   **Action**: Client provides reason/notes.
    *   **Updates**: Status -> `DECLINED`.

---

## 🔍 Critical Inspection Findings

### 1. State Machine Gaps
*   **Client Acceptance Loophole**: The `PortalController::accept` method does **not** verify if the estimate is `EXPIRED` or `DECLINED` before processing the acceptance. It only checks if it is *already* accepted. A savvy user with a valid signature link could potentially "Accept" an `EXPIRED` estimate.
    *   *Correction Reference*: `Estimate::validStatusTransitions` forbids `EXPIRED` -> `ACCEPTED`, but `PortalController` performs a direct `update()`, bypassing the model's `canTransitionTo` check.

### 2. Versioning & Stale Data
*   **Multi-Version Validity**: When a new version (v2) is created, the old version (v1) remains `SENT` (stats unchanged).
    *   *Risk*: A client could technically accept `v1` via an old email link *while* the team is working on `v2`.
    *   *Recommendation*: Creating a new version should probably mark previous versions in the family as `ARCHIVED` or `VOID` to prevent acceptance of outdated terms.

### 3. Approval Chain Dynamism
*   **Check Logic**: The `ApprovalChain` is assigned at *Save/Calculation* time. If parameters (like Discount) change *during* the approval process (unlikely if locked, but possible via race conditions or db edits), the chain steps are already generated and "baked in".
    *   *Mitigation*: The `ApprovalController::approve` uses `lockForUpdate` which is good, but does not re-validate the Chain rules against the current total.

### 4. Race Conditions
*   **Portal Actions**: `PortalController::accept` does **not** use `lockForUpdate()`. High concurrency (e.g., client clicking twice rapidly, or admin canceling while client accepts) could result in inconsistent states or lost event triggers.

## ✅ Suggested Improvements

1.  **Enforce State Machine in Portal**:
    ```php
    // In PortalController::accept
    if (!$estimate->canTransitionTo(Estimate::STATUS_ACCEPTED)) {
         abort(403, 'This estimate cannot be accepted in its current state.');
    }
    ```
2.  **Void Previous Versions**:
    *   In `EstimateService::createVersion`, set the status of the *parent/previous* estimate to `VOID` or `superseded` if it was `SENT`.
3.  **Lock Portal Actions**:
    *   Wrap `accept/decline` logic in `DB::transaction()` with `lockForUpdate()`.
