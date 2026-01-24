<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the roles/permissions.
     */
    public function index()
    {
        $roles = PermissionService::getRoles();
        $permissionsByCategory = PermissionService::getPermissionsByCategory();
        $permissionsForRoles = [];

        foreach ($roles as $roleKey => $roleInfo) {
            $permissionsForRoles[$roleKey] = PermissionService::getPermissionsForRole($roleKey);
        }

        return view('permissions.index', compact('roles', 'permissionsByCategory', 'permissionsForRoles'));
    }

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

        // DB Transaction for safety
        \Illuminate\Support\Facades\DB::transaction(function () use ($role, $selectedPermissions) {
            // Delete existing
            \App\Models\RolePermission::where('role', $role)->delete();

            // Insert new
            $data = [];
            foreach ($selectedPermissions as $permission) {
                $data[] = [
                    'role' => $role,
                    'permission' => $permission,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($data)) {
                \App\Models\RolePermission::insert($data);
            }
        });

        // Clear cache
        PermissionService::clearCache($role);

        return redirect()->route('permissions.index')
            ->with('success', 'Permissions updated successfully for ' . $roles[$role]['name']);
    }

    // Removed updatePermissionMapping as it's no longer needed
}
