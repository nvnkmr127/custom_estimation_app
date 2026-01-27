# PRD: User Roles, Permissions & Security

## 1. Overview
The **Security & Governance** module ensures that users only have access to the data and actions necessary for their role. It implements a robust RBAC (Role-Based Access Control) system tailored for an estimation environment.

## 2. User Stories
- **Data Isolation**: As a junior estimator, I should only see estimates I created or those I am specifically following.
- **Financial Guardrails**: As an admin, I want to restrict "Manual Discounting" beyond 5% to Managers only.
- **Auditability**: As a super admin, I want to see an activity log of who changed an estimate's status or deleted a product.

## 3. Functional Requirements

### 3.1 Role Hierarchy
1.  **Super Admin**: Unrestricted access to settings, users, and all estimates.
2.  **Estimator Admin**: Full access to estimates and templates, but cannot change system-wide billing settings.
3.  **Estimator**: Can create/edit their own estimates and follow shared ones.
4.  **Approver**: Specifically tasked with reviewing "Waiting Approval" estimates.

### 3.2 Dynamic Permissions
- Permissions are managed per-role via the `RolePermission` and `PermissionController`.
- Common permissions: `view_estimates`, `create_estimates`, `delete_products`, `manage_users`, `bypass_approval`.

### 3.3 Estimate "Followers"
- **Collaborative Access**: An estimator can explicitly "Add a Follower" to an estimate, granting them read or edit rights.
- **Manual Followers**: Managed via the `manualFollowers()` morph relationship on the `Estimate` model.

### 3.4 Activity Logging
- **The Trail**: Every mutation to an estimate, product, or setting is logged in the `activity_logs` table.
- **Attributes**: `subject_id`, `subject_type`, `user_id`, `action`, `description`.

## 4. Technical Specifications
- **Middleware**: `role:super_admin`, `can:update-estimate`.
- **Policies**: Standard Laravel Policies (e.g., `EstimatePolicy`, `ProductPolicy`) control authorization logic.
- **Model**: `User`, `RolePermission`, `ActivityLog`.

## 5. UI/UX Requirements
- **Permission Matrix**: A grid view for Admins to easily toggle permissions for different roles.
- **Audit View**: A searchable "Activity Log" feed accessible to Admins.
- **Restricted UI**: Buttons/Actions (like "Delete") should be hidden from the DOM if the user lacks the permission (using `@can` or `@role` Blade directives).
