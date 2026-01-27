# PRD: Estimate Creation Core & Lifecycle

## 1. Overview
The **Estimate Creation Core** is the entry point for the estimation workflow. It manages the lifecycle of an estimate from initial "Draft" to "Sent". It provides the fundamental CRUD operations and ensures data integrity through strict validation and state management.

## 2. User User Stories
- **Create**: As an estimator, I want to start a new estimate for a specific client so I can begin scoping a project.
- **Save Draft**: As an estimator, I want to save my progress without publishing so I can continue later.
- **Validation**: As a system, I want to ensure all required financial and client data is present before allowing a status change to "Sent".
- **Delete**: As an admin, I want to remove erroneous estimates to keep the system clean (Soft Delete).

## 3. Functional Requirements

### 3.1 Creation Flow (`create` & `store`)
- **Route**: `GET /estimates/create`, `POST /estimates`
- **Required Inputs**:
    - **Client/Lead**: Selected from active `Client` database.
    - **Date**: Defaults to today.
    - **Expiry Date**: Optional (Defaults to +30 days in logic if used).
    - **Currency**: Inherited from System Settings (default: USD).
    - **Status**: Initial status must be `draft` or `sent` (if authorized).
    - **Discount Type**: Percentage or Fixed (default: Percentage).
    - **Type**: `standard` (Flat List) or `room_based` (Hierarchical).

### 3.2 Update Flow (`edit` & `update`)
- **Route**: `GET /estimates/{id}/edit`, `PUT /estimates/{id}`
- **Concurrency Control**:
    - **Optimistic Locking**: The system expects a `last_update_timestamp`. If the server record is newer than the client's timestamp, the update is rejected to prevent overwriting colleague's work.
- **State Guarding**:
    - If an estimate is in a "Finalized" state (`Approved`, `Sent`, `Accepted`), editing it **MUST** trigger a **Branching/Versioning** event (see *Versioning PRD*) rather than a direct update, unless the user is a Super Admin.

### 3.3 Status Lifecycle
The estimate moves through the following states defined in `Estimate::STATUS_*`:
1.  **Draft**: Initial work-in-progress. Not visible to client.
2.  **Waiting Approval**: Submitted for internal review (if rules trigger).
3.  **Approved**: Passed internal review, ready for client.
4.  **Sent**: Emailed/Shared with the client.
5.  **Accepted**: Client signed and approved via Portal.
6.  **Declined**: Client rejected via Portal.
7.  **Expired**: Passed validity date.

## 4. Technical Specifications
- **Controller**: `EstimateController`
- **Service**: `EstimateService`
- **Model**: `Estimate`
- **Table**: `estimates`

### 4.1 Data Model
| Field | Type | Description |
| :--- | :--- | :--- |
| `estimate_number` | String | Unique Identifier (e.g., EST-2024-001). Generated via `EstimateService::generateNextNumber`. |
| `client_id` | FK | Relates to `clients` table. |
| `status` | Enum | Current lifecycle state. |
| `is_current_version` | Boolean | Flags the active version in a version family. |
| `approval_chain_id` | FK | Assigned approval logic (nullable). |

## 5. UI/UX Requirements
- **Sticky Header**: Save/Cancel buttons must remain visible while scrolling long item lists.
- **Client Search**: Dropdown with search capability (using Choices.js or similar) to handle large client lists.
- **Validation Feedback**:
    - Real-time frontend validation for "Required" fields.
    - Server-side validation errors displayed in a dedicated alert block at the top of the form.
