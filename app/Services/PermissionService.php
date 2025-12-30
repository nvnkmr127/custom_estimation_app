<?php

namespace App\Services;

class PermissionService
{
    /**
     * Define all available permissions in the system.
     */
    public const PERMISSIONS = [
        // User Management
        'manage_users' => 'Create, edit, and delete users',
        'view_users' => 'View user list and details',

        // Estimate Management
        'create_estimates' => 'Create new estimates',
        'edit_estimates' => 'Edit existing estimates',
        'delete_estimates' => 'Delete estimates',
        'view_estimates' => 'View estimates',
        'submit_estimates' => 'Submit estimates for approval',
        'approve_estimates' => 'Approve or reject estimates',
        'send_estimates' => 'Send estimates to clients',

        // Product Management
        'manage_products' => 'Create, edit, and delete products',
        'view_products' => 'View product catalog',
        'manage_categories' => 'Manage product categories',

        // Template Management
        'manage_templates' => 'Create and edit room templates',
        'manage_packages' => 'Create and edit item packages',
        'manage_pdf_templates' => 'Manage PDF Templates',
        'view_pdf_templates' => 'View PDF Templates',

        // Settings
        'manage_settings' => 'Access and modify system settings',
        'view_settings' => 'View system settings',

        // Reports
        'view_reports' => 'Access reporting features',
        'export_reports' => 'Export reports',

        // Perfex CRM
        'sync_perfex' => 'Sync data with Perfex CRM',
        'import_leads' => 'Import leads from Perfex',

        // Discounts
        'apply_discounts' => 'Apply discounts to estimates',
    ];

    /**
     * Map roles to their permissions.
     */
    public const ROLE_PERMISSIONS = [
        'super_admin' => [
            'manage_users',
            'view_users',
            'create_estimates',
            'edit_estimates',
            'delete_estimates',
            'view_estimates',
            'submit_estimates',
            'approve_estimates',
            'send_estimates',
            'manage_products',
            'view_products',
            'manage_categories',
            'manage_templates',
            'manage_packages',
            'manage_settings',
            'view_settings',
            'view_reports',
            'export_reports',
            'sync_perfex',
            'import_leads',
            'apply_discounts',
            'manage_pdf_templates',
            'view_pdf_templates',
        ],

        'estimator_admin' => [
            'view_users',
            'create_estimates',
            'edit_estimates',
            'delete_estimates',
            'view_estimates',
            'submit_estimates',
            'approve_estimates',
            'send_estimates',
            'manage_products',
            'view_products',
            'manage_categories',
            'manage_templates',
            'manage_packages',
            'view_settings',
            'view_reports',
            'export_reports',
            'sync_perfex',
            'import_leads',
            'apply_discounts',
            'manage_pdf_templates',
            'view_pdf_templates',
        ],

        'sales_manager' => [
            'create_estimates',
            'edit_estimates',
            'view_estimates',
            'submit_estimates',
            'approve_estimates',
            'send_estimates',
            'view_products',
            'view_settings',
            'view_reports',
            'sync_perfex',
            'apply_discounts',
            'view_pdf_templates',
        ],

        'sales' => [
            'create_estimates',
            'edit_estimates',
            'view_estimates',
            'submit_estimates',
            'view_products',
            'view_reports',
            'apply_discounts',
            'view_pdf_templates',
        ],
    ];

    /**
     * Get all permissions for a given role.
     */
    public static function getPermissionsForRole(string $role): array
    {
        return self::ROLE_PERMISSIONS[$role] ?? [];
    }

    /**
     * Check if a role has a specific permission.
     */
    public static function roleHasPermission(string $role, string $permission): bool
    {
        return in_array($permission, self::getPermissionsForRole($role));
    }

    /**
     * Get permission description.
     */
    public static function getPermissionDescription(string $permission): string
    {
        return self::PERMISSIONS[$permission] ?? 'Unknown permission';
    }

    /**
     * Get all permissions grouped by category.
     */
    public static function getPermissionsByCategory(): array
    {
        return [
            'User Management' => [
                'manage_users',
                'view_users',
            ],
            'Estimates' => [
                'create_estimates',
                'edit_estimates',
                'delete_estimates',
                'view_estimates',
                'submit_estimates',
                'approve_estimates',
                'send_estimates',
            ],
            'Products & Catalog' => [
                'manage_products',
                'view_products',
                'manage_categories',
                'manage_templates',
                'manage_packages',
            ],
            'System' => [
                'manage_settings',
                'view_settings',
                'view_reports',
                'export_reports',
            ],
            'Integrations' => [
                'sync_perfex',
                'import_leads',
            ],
            'Pricing' => [
                'apply_discounts',
            ],
        ];
    }

    /**
     * Get all available roles with descriptions.
     */
    public static function getRoles(): array
    {
        return [
            'super_admin' => [
                'name' => 'Super Admin',
                'description' => 'Full system access including user management',
                'color' => 'purple',
            ],
            'estimator_admin' => [
                'name' => 'Estimator Admin',
                'description' => 'Manage estimates, products, and templates',
                'color' => 'indigo',
            ],
            'sales_manager' => [
                'name' => 'Sales Manager',
                'description' => 'Create and approve estimates',
                'color' => 'blue',
            ],
            'sales' => [
                'name' => 'Sales',
                'description' => 'Create and manage own estimates',
                'color' => 'gray',
            ],
        ];
    }
}
