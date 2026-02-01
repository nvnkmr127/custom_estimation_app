<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebhookConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Admin User
        $this->admin = User::factory()->create(['role' => 'super_admin']);

        // Setup Regular User
        $this->user = User::factory()->create(['role' => 'estimator']);
    }

    /** @test */
    public function only_admins_can_manage_webhooks()
    {
        $response = $this->actingAs($this->user)->getJson(route('admin.webhooks.index'));
        $response->assertForbidden();

        $response = $this->actingAs($this->user)->postJson(route('admin.webhooks.store'), []);
        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_create_webhook_endpoint()
    {
        $payload = [
            'name' => 'Zapier Integration',
            'url' => 'https://hooks.zapier.com/triggers/12345',
            'http_method' => 'POST',
            'events' => ['estimate.created', 'estimate.approved'],
            'status' => 'active',
            'secret' => 'super-secret-key',
            'rate_limit' => 60,
        ];

        $response = $this->actingAs($this->admin)->postJson(route('admin.webhooks.store'), $payload);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Zapier Integration']);

        $this->assertDatabaseHas('webhooks', [
            'name' => 'Zapier Integration',
            'url' => 'https://hooks.zapier.com/triggers/12345',
            'http_method' => 'POST',
            'rate_limit' => 60,
        ]);
    }

    /** @test */
    public function it_validates_webhook_input()
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.webhooks.store'), [
            'name' => '', // Required
            'url' => 'not-a-url', // Invalid URL
            'http_method' => 'INVALID', // Invalid Method
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'url', 'http_method', 'events']);
    }

    /** @test */
    public function admin_can_update_webhook_endpoint()
    {
        $webhook = WebhookConfig::create([
            'name' => 'Old Name',
            'url' => 'https://example.com',
            'events' => ['*'],
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->admin)->putJson(route('admin.webhooks.update', $webhook), [
            'name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'New Name']);

        $this->assertDatabaseHas('webhooks', ['id' => $webhook->id, 'name' => 'New Name']);
    }

    /** @test */
    public function admin_can_disable_webhook_endpoint()
    {
        $webhook = WebhookConfig::create([
            'name' => 'To Delete',
            'url' => 'https://example.com',
            'events' => ['*'],
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->admin)->deleteJson(route('admin.webhooks.destroy', $webhook));

        $response->assertOk();
        $this->assertSoftDeleted('webhooks', ['id' => $webhook->id]);
    }
}
