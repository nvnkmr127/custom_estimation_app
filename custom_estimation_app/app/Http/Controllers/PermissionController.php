<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PermissionService;

class PermissionController extends Controller
{
    /**
     * Show the form for editing role permissions.
     */
    public function edit($role)
    {
        $roles = PermissionService::getRoles();

        if (!isset($roles[$role])) {
            abort(404, 'Role not found');
        }

        $roleInfo = $roles[$role];
        $currentPermissions = PermissionService::getPermissionsForRole($role);
        $permissionsByCategory = PermissionService::getPermissionsByCategory();
        $allPermissions = PermissionService::PERMISSIONS;

        return view('permissions.edit', compact('role', 'roleInfo', 'currentPermissions', 'permissionsByCategory', 'allPermissions'));
    }

    /**
     * Update role permissions.
     */
    public function update(Request $request, $role)
    {
        $roles = PermissionService::getRoles();

        if (!isset($roles[$role])) {
            abort(404, 'Role not found');
        }

        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);

        $selectedPermissions = $validated['permissions'] ?? [];

        // Update the PermissionService mapping
        // Note: This updates the code file directly
        $this->updatePermissionMapping($role, $selectedPermissions);

        return redirect()->route('permissions.index')
            ->with('success', 'Permissions updated successfully for ' . $roles[$role]['name']);
    }

    /**
     * Update permission mapping in PermissionService.
     */
    private function updatePermissionMapping($role, $permissions)
    {
        $filePath = app_path('Services/PermissionService.php');
        $content = file_get_contents($filePath);

        // Find the ROLE_PERMISSIONS array
        $pattern = "/'$role'\s*=>\s*\[(.*?)\]/s";

        // Format new permissions
        $permissionsStr = implode(",\n            ", array_map(function ($p) {
            return "self::$p";
        }, $permissions));

        $replacement = "'$role' => [\n            $permissionsStr,\n        ]";

        $content = preg_replace($pattern, $replacement, $content);

        file_put_contents($filePath, $content);

        // Clear any cached config
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}
