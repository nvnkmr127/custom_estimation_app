# Secure Estimate Listing & Preview Implementation

## Overview
This feature provides internal team members with a centralized way to preview estimates within the client portal environment. It includes robust access controls, audit logging, and visual indicators to ensure that only authorized personnel can access the master list and that they are aware of their "Preview Mode" status.

## Components

### 1. Master Preview List
- **Route**: `GET /portal/preview/estimates` (Named: `portal.preview-list`)
- **Controller**: `PortalController@previewList`
- **View**: `resources/views/portal/estimates/index.blade.php`
- **Purpose**: Displays a paginated list of all estimates in the system, formatted in the portal's aesthetic.

### 2. Security & Access Control
- **Middleware**: Protected by `auth` and `role:super_admin,estimator_admin,sales_manager,sales`.
- **Controller-Level Validation**: Explicit re-verification of roles within the controller method as a secondary security layer.
- **Client Exclusion**: Clients (who access individual estimates via signed routes) are strictly blocked from the listing page. Any unauthorized attempt triggers a security audit log.

### 3. Audit Logging
Comprehensive logging via `ActivityLog` model:
- `portal_preview_accessed`: Logged every time an internal member accesses the master list.
- `security_breach_attempt`: Logged if an unauthorized user attempts to manipulate the URL to access the listing.

### 4. Visual Indicators
- **Master List Banner**: A prominent yellow banner on the listing page identifying it as "Internal Preview Mode".
- **Single View Indicator**: A purple sticky banner on individual estimate portal pages (`portal.estimates.show`) that appears only for logged-in staff. It provides quick links back to the "Master List" or the "Edit Estimate" page.

## Testing & Verification
- **Bypass Prevention**: Direct URL manipulation to `/portal/preview/estimates` by an unauthenticated user or a user without the correct role will result in a 403 Forbidden error.
- **Signed Routes**: Individual estimate access for clients remains protected by Laravel's signed URL signature verification.
- **Data Filtering**: The master list is completely hidden from the standard client portal flow.

## Maintenance
When adding new internal roles in the future, ensure they are added to the `role` middleware in `routes/web.php` and the validation logic in `PortalController@previewList`.
