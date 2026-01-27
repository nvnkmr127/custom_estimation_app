# PRD: Client Management & CRM Integration

## 1. Overview
The **Client Management** module centralizes all stakeholder data. It provides a CRM-like experience while strictly synchronizing with external systems like **Perfex CRM** to ensure there is a "Single Source of Truth" for client billing and contact details.

## 2. User Stories
- **Lead Import**: As an estimator, I want to pull my latest leads from Perfex CRM so I can quote them immediately.
- **Bi-directional Sync**: As an admin, I want changes made to a client's email in this app to update Perfex (and vice-versa).
- **Interaction History**: As a user, I want to see every estimate, comment, and phone call associated with a client in one profile view.

## 3. Functional Requirements

### 3.1 Client Profile
- **Identity**: Name, Company, Email, Phone.
- **Billing**: Address, Preferred Currency.
- **CRM Metadata**: `perfex_id`, `lead_source`, `assigned_to` (Estimator).

### 3.2 Perfex CRM Integration
- **Sync Engine**: `PerfexApiService` handles communication with the external CRM API.
- **Webhook Listener**: `PerfexWebhookController` listens for "Customer Created/Updated" events from the CRM to auto-update the local database.
- **Manual Sync**: Button on the Client List to "Refresh from CRM".

### 3.3 Linkage to Estimates
- Estimates are bound to a `client_id`.
- The system prevents deleting clients who have "Active" or "Accepted" estimates (Soft delete only).

## 4. Technical Specifications
- **Model**: `Client`
- **Controller**: `ClientController`
- **Service**: `PerfexApiService` (Found in `App\Services`)
- **Webhooks**: `POST /webhooks/perfex` (CSRF exempt).

## 5. UI/UX Requirements
- **Unified Profile**: A detail view for each client showing:
    - Contact Card.
    - Linked Estimates (with statuses).
    - Task List associated with this client.
    - Automation Status (is this client currently on a nurture sequence?).
- **Search & Filtering**: Search by Company name, Email, or Perfex ID.
