# Approval Guards & Route Access Analysis

## Issue
The user is experiencing a `403 Forbidden` error when trying to fetch client details via `/clients/{id}` (`/clients/12`) from the Estimate Creation page.

## Root Cause Analysis
1.  **Frontend**: The frontend makes a fetch request to `/clients/12`.
2.  **Routes**: `Route::resource('clients', ClientController::class)` maps `GET /clients/{id}` to `ClientController::show`.
3.  **Controller**: `ClientController::__construct` calls `$this->authorizeResource(Client::class, 'client')`.
    *   This automatically applies standard policy checks: `index` -> `viewAny`, `show` -> `view`, `create` -> `create`, etc.
4.  **Policy**: `ClientPolicy::view($user, $client)` checks `$user->hasPermission('view_estimates')`.
5.  **Role/Permission**:
    *   If the currently logged-in user does **not** have the `view_estimates` permission (or role granting it), the policy returns `false`.
    *   This triggers a 403.

## Access Requirements
The `/clients/{id}` endpoint serves two purposes:
1.  **Administrative**: Viewing full client profile (CRM-like feature).
2.  **Operational**: Filling the Estimate "Bill To" details (AJAX fetch).

**Conflict**: An "Estimator" who can *create* estimates (`create_estimates` permission) might *not* have `view_estimates` permission (if that implies viewing *all* estimates or client CRM data).
However, to create an estimate effectively, they **must** be able to read client data to populate the fields.

## Recommendations
1.  **Policy Adjustment**:
    *   Allow `view` if the user has `create_estimates` permission *OR* `view_estimates`.
    *   Or, verify if `view_estimates` is missing from the user's role.

2.  **Dedicated JSON Endpoint**:
    *   Create a specific endpoint `GET /api/clients/{client}/details` intended for form hydration, authorized by `create_estimates` permission specifically.
    *   Current `show` method returns a full HTML view *or* JSON based on `wantsJson()`.

3.  **Immediate Fix**:
    *   Modify `ClientPolicy::view` to allow access if the user can `create_estimates`.

## Action Plan
1.  Check `ClientPolicy::view`.
2.  Update it to allow `create_estimates` permission holders to view clients (at least their details for estimation).
