# PRD: Estimate Versioning & Branching

## 1. Overview
In professional estimation, proposals often go through multiple iterations ("Draft", "Revision 1", "Revision 2"). The **Versioning & Branching** module ensures that once an estimate is presented to a client or approved internally, its history is preserved. New changes result in a new "Version" rather than overwriting the historical record.

## 2. User Stories
- **Preservation**: As an admin, I want to ensure that "Sent" estimates cannot be secretly changed, protecting our liability.
- **Revisions**: As an estimator, I want to create a "V2" of a proposal based on client feedback without starting from scratch.
- **Comparison**: As a user, I want to see what changed between V1 and V2.

## 3. Functional Requirements

### 3.1 Version Lifecycle
- **Version Number**: An integer counter (`version`) incrementing per "Family".
- **Family**: Defined by a root parent. All versions share a `parent_id` pointing to the original Version 1 (or V1 is the root).
- **Current Version**: Only **one** version in a family can have `is_current_version = true`. This is the "Live" version.

### 3.2 Branching Triggers
The system **automatically** branches (creates a new version) in `EstimateService::updateEstimate` when:
1.  **Status Lock**: The current status is `Sent`, `Accepted`, `Declined`, or `Expired`.
2.  **Access Lock**: The user editing is NOT the Creator and NOT an Admin (Collaborator edits trigger revisions).
3.  **Manual Trigger**: User clicks "Create New Version" explicitly.

### 3.3 Branching Logic
When a branch occurs:
1.  **Replication**: The `Estimate` record is duplicated.
2.  **Hierarchy**: `parent_id` is set to the original root ID.
3.  **Naming**: `estimate_number` is appended (e.g., `EST-100-v2`).
4.  **Content**: All `estimate_sections` and `estimate_items` are deep-cloned to the new ID.
5.  **State**: The new version starts as `Draft`. The old version remains `Sent`/`Locked`.
6.  **Current Flag**: The **New** version becomes `is_current_version = true` (Active Draft), deprecating the old one as "History".

### 3.4 Approval of Version
- When a Version is "Approved" (marked as the one to proceed with):
    - It sets `is_current_version = true`.
    - All *sibling* versions are set to `is_current_version = false`.

## 4. Technical Specifications
- **Service Method**: `EstimateService::createVersion(Estimate $estimate)`
- **DB Transactions**: Critical. Branching must be atomic. Failure to duplicate items must rollback the estimate creation.
- **Activity Log**: Every branch event is logged as `created_proposal` in `activity_logs`.

## 5. UI/UX Requirements
- **Version Selector**: A dropdown in the `Show` view allowing easy navigation between V1, V2, etc.
- **Diff View**: (Future) Visual highlighting of added/removed items between versions.
- **Read-Only Mode**: When viewing a non-current or locked version, the UI must strictly disable all editing inputs and show a "This is an archived version" banner.
