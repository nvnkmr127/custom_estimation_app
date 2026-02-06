# SSO Configuration Documentation

This document describes the environment variables required for the SSO integration.

## Environment Variables

### `AUTH_SSO_ENABLED`
- **Description**: Toggle to enable or disable the SSO authentication feature.
- **Type**: Boolean (`true`|`false`)
- **Default**: `false`

### `AUTH_CORE_URL`
- **Description**: The base URL for the external SSO authentication service (Auth Core).
- **Type**: URL
- **Example**: `https://auth.example.com`

### `AUTH_CORE_PUBLIC_KEY`
- **Description**: The public key used to verify RS256 JWT tokens issued by the Auth Core.
- **Type**: String (Multiline support via quoted strings in `.env`)
- **Format**: PEM format (`-----BEGIN PUBLIC KEY----- ... -----END PUBLIC KEY-----`)
### `SSO_JWT_AUDIENCE`
- **Description**: The expected audience (`aud`) claim for the JWT.
- **Type**: String
- **Default**: `${APP_URL}`

### `SSO_JTI_VALIDATION`
- **Description**: Toggle replay protection by validating the unique token ID (`jti`).
- **Type**: Boolean
- **Default**: `true`

### `SSO_JTI_TTL`
- **Description**: Time-to-live for the JTI store in the cache (seconds).
- **Type**: Integer
- **Default**: `3600`

### `SSO_CENTRALIZED_LOGOUT`
- **Description**: If enabled, local logout will redirect to the Auth Core logout endpoint.
- **Type**: Boolean
- **Default**: `true`
