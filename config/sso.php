<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SSO Enabled
    |--------------------------------------------------------------------------
    |
    | This flag determines if the SSO integration is active.
    |
    */

    'enabled' => env('APP_ENV') === 'local' ? false : env('AUTH_SSO_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Auth Core URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the external SSO authentication service.
    |
    */

    'auth_core_url' => env('AUTH_CORE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Public Key
    |--------------------------------------------------------------------------
    |
    | The public key used for JWT verification. This can be the raw key string
    | or a path to the key file.
    |
    */

    'public_key' => env('AUTH_CORE_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | JWT Audience
    |--------------------------------------------------------------------------
    |
    | The intended recipient of the token, typically the application's base URL.
    |
    */

    'jwt_audience' => env('SSO_JWT_AUDIENCE', env('APP_URL')),

    /*
    |--------------------------------------------------------------------------
    | Role Mapping
    |--------------------------------------------------------------------------
    |
    | Map JWT roles (from the 'role' claim) to local application roles.
    |
    */

    'role_mapping' => [
        'super_admin' => 'super_admin',
        'admin' => 'super_admin',
        'estimator_admin' => 'estimator_admin',
        'manager' => 'estimator_manager',
        'user' => 'estimator',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Role
    |--------------------------------------------------------------------------
    |
    | The role assigned to new SSO users if no mapping matches or role claim is missing.
    |
    */

    'default_role' => 'estimator',

    /*
    |--------------------------------------------------------------------------
    | Centralized Logout
    |--------------------------------------------------------------------------
    |
    | If enabled, logging out of this application will also redirect the user
    | to the Auth Core logout endpoint.
    |
    */

    'centralized_logout' => env('SSO_CENTRALIZED_LOGOUT', true),

    /*
    |--------------------------------------------------------------------------
    | Logout Path
    |--------------------------------------------------------------------------
    |
    | The path suffix for the Auth Core logout endpoint.
    |
    */

    'logout_path' => '/logout',

    /*
    |--------------------------------------------------------------------------
    | Replay Protection (JTI Validation)
    |--------------------------------------------------------------------------
    |
    | If enabled, the application will store and validate the 'jti' claim
    | to prevent token reuse (replay attacks).
    |
    */

    'jti_validation' => env('SSO_JTI_VALIDATION', true),

    /*
    |--------------------------------------------------------------------------
    | JTI TTL (Seconds)
    |--------------------------------------------------------------------------
    |
    | How long to store the JTI in the cache. Should be slightly longer
    | than the JWT expiration time.
    |
    */

    'jti_ttl' => env('SSO_JTI_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Sync Token
    |--------------------------------------------------------------------------
    |
    | Shared secret token used to authenticate sync requests from the Auth Portal.
    |
    */
    'sync_token' => env('SSO_SYNC_TOKEN'),

];
