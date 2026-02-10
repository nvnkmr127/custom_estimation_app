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
        // Simple token-based security
        $token = $request->header('X-SSO-Sync-Token');
        if (!$token || $token !== config('sso.sync_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'roles' => PermissionService::getRoles(),
            'permissions' => PermissionService::PERMISSIONS,
            'groups' => PermissionService::getPermissionsByCategory(),
        ]);
    }
}
