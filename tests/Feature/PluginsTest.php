<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Plugin;
use App\Models\PluginModule;
use App\Models\PluginModuleLog;
use App\Models\Estimate;
use App\Models\Client;
use App\Models\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use App\Core\Events\Estimates\EstimateApproved;
use Livewire\Livewire;
use Tests\TestCase;

class PluginsTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $normalUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Admin user (Estimator Admin)
        $this->adminUser = User::factory()->create([
            'role' => 'estimator_admin',
            'email_verified_at' => now(),
        ]);

        // Grant manage_plugins permission
        RolePermission::firstOrCreate([
            'role' => 'estimator_admin',
            'permission' => 'manage_plugins',
        ]);

        // Create standard Estimator user (no manage_plugins permission)
        $this->normalUser = User::factory()->create([
            'role' => 'estimator',
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function estimator_user_cannot_access_plugins_dashboard()
    {
        $this->actingAs($this->normalUser)
            ->get(route('admin.plugins.index'))
            ->assertStatus(403);
    }

    /** @test */
    public function admin_user_can_access_plugins_dashboard()
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.plugins.index'))
            ->assertStatus(200);
    }

    /** @test */
    public function admin_can_toggle_plugin_state_via_livewire()
    {
        $plugin = Plugin::first();
        $this->assertNotNull($plugin);
        $this->assertFalse($plugin->is_active);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Admin\Plugins\Index::class)
            ->call('togglePluginStatus', $plugin->id);

        $this->assertTrue($plugin->fresh()->is_active);
    }

    /** @test */
    public function outbound_webhook_module_triggers_on_matching_domain_event()
    {
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(['ok' => true], 200),
        ]);

        // Activate plugin and create an active outbound module
        $plugin = Plugin::where('key', 'slack')->first();
        $plugin->update(['is_active' => true]);

        $module = PluginModule::create([
            'plugin_id' => $plugin->id,
            'name' => 'Slack Alert',
            'key' => 'slack_alert',
            'is_active' => true,
            'type' => 'outbound',
            'event_name' => 'estimate.approved',
            'settings' => [
                'url' => 'https://hooks.slack.com/services/test',
                'method' => 'POST',
            ]
        ]);

        // Create test estimate
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'grand_total' => 15000.00,
            'estimate_status' => 'approved',
        ]);

        // Dispatch Domain Event
        $event = new EstimateApproved($estimate, $this->adminUser->id);
        
        // Let the listener handle it directly
        $listener = app(\App\Listeners\PluginEventListener::class);
        $listener->handle($event);

        // Verify HTTP call was made
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'hooks.slack.com') &&
                   $request->method() === 'POST' &&
                   str_contains($request->body(), 'estimate.approved');
        });

        // Verify Log was written
        $this->assertDatabaseHas('plugin_module_logs', [
            'plugin_module_id' => $module->id,
            'direction' => 'outbound',
            'status' => 'success',
            'response_code' => 200,
        ]);
    }

    /** @test */
    public function inbound_catch_endpoint_can_update_estimate_status()
    {
        // Setup Plugin and Inbound Module
        $plugin = Plugin::where('key', 'custom')->first();
        $plugin->update(['is_active' => true]);

        $module = PluginModule::create([
            'plugin_id' => $plugin->id,
            'name' => 'Stripe Inbound Sync',
            'key' => 'stripe_sync',
            'is_active' => true,
            'type' => 'inbound',
            'settings' => [
                'action_type' => 'update_estimate',
                'action_config' => [
                    'identifier' => 'charge.metadata.estimate_number',
                    'status_field' => 'charge.status',
                    'status_map' => [
                        'succeeded' => 'accepted'
                    ]
                ]
            ]
        ]);

        // Create Estimate to update
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'estimate_status' => 'sent',
        ]);

        $payload = [
            'charge' => [
                'status' => 'succeeded',
                'metadata' => [
                    'estimate_number' => $estimate->estimate_number
                ]
            ]
        ];

        // Call target callback endpoint
        $response = $this->postJson(route('admin.plugins.catch', ['uuid' => $module->uuid]), $payload);

        $response->assertStatus(200);

        // Verify status transitioned successfully
        $this->assertEquals('accepted', $estimate->fresh()->estimate_status);

        // Verify Inbound Log entry
        $this->assertDatabaseHas('plugin_module_logs', [
            'plugin_module_id' => $module->id,
            'direction' => 'inbound',
            'status' => 'success',
            'response_code' => 200,
        ]);
    }

    /** @test */
    public function inbound_catch_endpoint_can_create_lead()
    {
        // Setup Inbound Module for HubSpot Client Sync
        $plugin = Plugin::where('key', 'hubspot')->first();
        $plugin->update(['is_active' => true]);

        $module = PluginModule::create([
            'plugin_id' => $plugin->id,
            'name' => 'HubSpot Lead Receiver',
            'key' => 'hubspot_lead',
            'is_active' => true,
            'type' => 'inbound',
            'settings' => [
                'action_type' => 'create_client',
                'action_config' => [
                    'name' => 'contact.firstname',
                    'email' => 'contact.email',
                    'phone' => 'contact.phone',
                    'company' => 'contact.company'
                ]
            ]
        ]);

        $payload = [
            'contact' => [
                'firstname' => 'Alice Green',
                'email' => 'alice@test-hubspot.com',
                'phone' => '9876543210',
                'company' => 'Green Energy Ltd'
            ]
        ];

        $response = $this->postJson(route('admin.plugins.catch', ['uuid' => $module->uuid]), $payload);

        $response->assertStatus(200);

        // Verify client was created
        $this->assertDatabaseHas('clients', [
            'email' => 'alice@test-hubspot.com',
            'name' => 'Alice Green',
            'phone' => '9876543210',
            'company' => 'Green Energy Ltd'
        ]);
    }

    /** @test */
    public function admin_cannot_save_invalid_json_in_module_settings()
    {
        $plugin = Plugin::first();

        \Livewire\Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Admin\Plugins\Index::class)
            ->set('selectedPluginId', $plugin->id)
            ->set('moduleName', 'Test Invalid JSON')
            ->set('moduleKey', 'test_invalid_json')
            ->set('moduleHeadersInput', '{"invalid_json": ')
            ->call('saveModule')
            ->assertHasErrors(['moduleHeadersInput']);
    }
}
