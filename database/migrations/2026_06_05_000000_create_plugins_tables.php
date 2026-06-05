<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('description')->nullable();
            $table->string('version')->default('1.0.0');
            $table->boolean('is_active')->default(false);
            $table->text('config_schema')->nullable(); // JSON stored as text
            $table->text('config')->nullable(); // JSON stored as text
            $table->timestamps();
        });

        Schema::create('plugin_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained('plugins')->onDelete('cascade');
            $table->string('name');
            $table->string('key')->unique();
            $table->boolean('is_active')->default(true);
            $table->string('type'); // 'outbound', 'inbound', 'sync'
            $table->string('event_name')->nullable(); // e.g., 'estimate.approved'
            $table->string('uuid')->unique(); // unique callback route token
            $table->text('settings')->nullable(); // JSON config settings
            $table->timestamps();
        });

        Schema::create('plugin_module_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_module_id')->constrained('plugin_modules')->onDelete('cascade');
            $table->string('direction'); // 'inbound', 'outbound'
            $table->string('status'); // 'success', 'failed'
            $table->text('payload')->nullable(); // JSON text
            $table->text('headers')->nullable(); // JSON text
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('latency_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // Seed initial permissions for plugin management
        $now = now();
        $rolesWithPlugins = ['super_admin', 'estimator_admin'];
        $insertPermissions = [];
        foreach ($rolesWithPlugins as $role) {
            $exists = DB::table('role_permissions')
                ->where('role', $role)
                ->where('permission', 'manage_plugins')
                ->exists();

            if (!$exists) {
                $insertPermissions[] = [
                    'role' => $role,
                    'permission' => 'manage_plugins',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($insertPermissions)) {
            DB::table('role_permissions')->insert($insertPermissions);
        }

        // Seed default plugins
        $defaultPlugins = [
            [
                'name' => 'Slack Integration',
                'key' => 'slack',
                'description' => 'Push status updates, approvals, and comments directly to your Slack channels.',
                'version' => '1.0.0',
                'is_active' => false,
                'config_schema' => json_encode([
                    ['name' => 'webhook_url', 'label' => 'Slack Webhook URL', 'type' => 'text', 'placeholder' => 'https://hooks.slack.com/services/...', 'required' => true]
                ]),
                'config' => json_encode(['webhook_url' => '']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'QuickBooks Connector',
                'key' => 'quickbooks',
                'description' => 'Synchronize estimates, invoices, and payment statuses with QuickBooks Online.',
                'version' => '1.1.2',
                'is_active' => false,
                'config_schema' => json_encode([
                    ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'placeholder' => 'QuickBooks client id', 'required' => true],
                    ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'placeholder' => '••••••••', 'required' => true]
                ]),
                'config' => json_encode(['client_id' => '', 'client_secret' => '']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'HubSpot CRM Sync',
                'key' => 'hubspot',
                'description' => 'Automatically create or update deals and contacts when estimates are created or approved.',
                'version' => '2.0.1',
                'is_active' => false,
                'config_schema' => json_encode([
                    ['name' => 'api_key', 'label' => 'HubSpot Private Access Token', 'type' => 'password', 'placeholder' => 'pat-na1-••••••••', 'required' => true]
                ]),
                'config' => json_encode(['api_key' => '']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Custom Connector',
                'key' => 'custom',
                'description' => 'Trigger customizable JSON payloads on any estimation platform event or process inbound REST calls.',
                'version' => '1.0.0',
                'is_active' => false,
                'config_schema' => json_encode([]),
                'config' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('plugins')->insert($defaultPlugins);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_module_logs');
        Schema::dropIfExists('plugin_modules');
        Schema::dropIfExists('plugins');
        DB::table('role_permissions')->where('permission', 'manage_plugins')->delete();
    }
};
