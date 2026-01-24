<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role')->index();
            $table->string('permission');
            $table->timestamps();

            $table->unique(['role', 'permission']);
        });

        // Seed initial permissions from the hardcoded service (manual copy to ensure no dependency issues during migration)
        $initialPermissions = [
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
                'manage_automations',
                'view_automations'
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
                'manage_automations',
                'view_automations'
            ],
            'estimator_manager' => [
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
                'view_pdf_templates'
            ],
            'estimator' => [
                'create_estimates',
                'edit_estimates',
                'view_estimates',
                'submit_estimates',
                'view_products',
                'view_reports',
                'apply_discounts',
                'view_pdf_templates'
            ]
        ];

        $now = now();
        $insertData = [];

        foreach ($initialPermissions as $role => $permissions) {
            foreach ($permissions as $permission) {
                $insertData[] = [
                    'role' => $role,
                    'permission' => $permission,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($insertData)) {
            DB::table('role_permissions')->insert($insertData);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
