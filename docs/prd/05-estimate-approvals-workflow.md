# PRD: Estimate Approvals & Workflow

## 1. Overview
High-value or high-discount estimates require managerial oversight. The **Approvals** module automates this governance, routing estimates to specific "Approval Chains" based on business logic rules.

## 2. User Stories
- **Governance**: As a business owner, I want to prevent sales reps from sending estimates with >20% discount without my approval.
- **Workflow**: As a manager, I want to receive a notification when an estimate needs my review.
- **Speed**: As an estimator, I want approved estimates to automatically switch to "Approved" status so I can send them immediately.

## 3. Functional Requirements

### 3.1 Approval Triggers
The system evaluates the estimate against `ApprovalChain` rules in `EstimateService::recalculateTotals`:
1.  **Discount Threshold**: Trigger if `Discount % >= Chain.min_discount`.
2.  **Amount Threshold**: Trigger if `Grand Total >= Chain.min_amount`.
3.  **Priority**: Discount-based chains take precedence over Amount-based chains.

### 3.2 The Approval Lifecycle
1.  **Submission**: User clicks "Submit for Approval". Status -> `waiting_approval`.
2.  **Routing**: The system identifies the active `ApprovalChain` and creates `EstimateApproval` records for the Approvers in the first Step (Order 1).
3.  **Notifications**: Approvers receive Email/In-App notification.
4.  **Decision**:
    - **Approve**: User marks approval. If all users in Step 1 approve, system activates Step 2.
    - **Reject/Request Change**: User adds comment. Status -> `Declined` or `Draft` (Revert).
    - **Delegate**: (Future) Reassign approval to another user.
5.  **Completion**: When all steps are approved -> Estimate Status -> `Approved`.

### 3.3 Bypass
- **Super Admins**: Can force-approve or self-approve any estimate, bypassing the chain.
- **Self-Approval**: If the Creator is *also* the Approver in the chain, the system should ideally auto-approve that step (configurable).

## 4. Technical Specifications
- **Models**:
    - `ApprovalChain` (Rules)
    - `ApprovalChainStep` (Users per step)
    - `EstimateApproval` (Transaction record of a specific approval request)
- **Controller**: `ApprovalController`
- **Logic**: `Estimate::submitForApproval()`, `Estimate::isFullyApproved()`

## 5. UI/UX Requirements
- **Banner**: "Waiting for Approval" banner on the Estimate Show page blocking the "Send to Client" button.
- **Timeline**: A visual timeline showing who approved, who is pending, and timestamps.
- **Checklists**: (Optional) Approvers may be required to check off specific compliance items (e.g., "Checked Margin", "Verified Scope") before clicking Approve.
