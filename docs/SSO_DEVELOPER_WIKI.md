# SSO Developer Wiki

## Overview
The Single Sign-On (SSO) integration uses **OpenID Connect (OIDC)** inspired JWT authentication. When a guest attempts to access a protected route, they are redirected to the **Auth Core** (Nexus Identity) to provide credentials. Upon success, they are returned with a `token` parameter containing an **RS256 signed JWT**.

## Environment Variables
Refer to the [Main Configuration Guide](./SSO_CONFIG.md) for a full list of `.env` variables.

## Authentication Flow
1. **Redirect**: `Authenticate` middleware detects guest status and redirects to `{AUTH_CORE_URL}/login?redirect={APP_URL}`.
2. **Callback**: The `SsoController@callback` receives the JWT.
3. **Verification**: Token is verified for signature, expiration, audience, issuer, and uniqueness (JTI).
4. **Provisioning**: User is found by email or created.
5. **Login**: Session is regenerated and the user is authenticated.

## Role Mapping
Roles are mapped in `config/sso.php`. To add a new mapping, update the `role_mapping` array:
```php
'role_mapping' => [
    'nexus_admin' => 'super_admin',
    'nexus_manager' => 'estimator_manager',
],
```

## Failure Modes & Troubleshooting
| Status | Cause | Action |
| :--- | :--- | :--- |
| **403 Forbidden** | Signature mismatch, Expired token, or Replay detected | Check the `AUTH_CORE_PUBLIC_KEY` or ensure system clocks are synced (NTP). |
| **Redirect Loop** | Token accepted but user lacks local permissions | Check the `role_mapping` and ensure the user's role exists locally. |
| **Missing Token** | Auth Core didn't pass the `token` parameter | Verify the Callback URL configuration in Nexus Identity. |

## Local Development Notes
To test SSO locally without a live Auth Core:
1. Ensure `AUTH_SSO_ENABLED=true`.
2. Use the provided test suite `tests/Feature/Auth/SsoCallbackTest.php` which demonstrates how to generate valid local test tokens.
3. If using a local Nexus Identity container, ensure `AUTH_CORE_URL` is accessible from inside your application environment (e.g., using `host.docker.internal`).
