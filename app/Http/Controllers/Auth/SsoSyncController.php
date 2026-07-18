<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class SsoSyncController extends Controller
{
    /**
     * Provide roles and permissions for the Auth Portal to sync.
     */
    public function index(Request $request)
    {
        // Token-based security. Use a constant-time comparison to avoid leaking the
        // secret via timing, and require a configured token so an empty config can't be matched.
        $token = $request->header('X-SSO-Sync-Token');
        $expected = config('sso.sync_token');
        if (!$token || !$expected || !hash_equals((string) $expected, (string) $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'roles' => PermissionService::getRoles(),
            'permissions' => PermissionService::PERMISSIONS,
            'groups' => PermissionService::getPermissionsByCategory(),
        ]);
    }
}
