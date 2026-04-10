<?php

namespace Tests\Feature;

use App\Models\Estimate;
use App\Models\NurtureRule;
use App\Models\User;
use App\Models\ActivityLog;
use App\Notifications\EstimateNurtureNotification;
use App\Notifications\HotLeadAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdvancedNurtureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_nurture_rule()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->post(route('settings.nurture.rules.store'), [
            'name' => 'Test Rule',
            'trigger_type' => 'time_based',
            'trigger_days' => 3,
            'action_type' => 'email',
            'condition_logic' => ['view_count' => 0],
            'action_data' => ['subject' => 'Hello'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('nurture_rules', ['name' => 'Test Rule']);
    }

    public function test_nurture_command_sends_email()
    {
        Notification::fake();

        $now = now();
        $this->travelTo($now);

        // 0. Enable System
        \App\Models\Setting::create(['key' => 'nurture_system_active', 'value' => true]);

        // 1. Create Rule
        NurtureRule::create([
            'name' => '3 Day Followup',
            'trigger_type' => 'time_based',
            'trigger_days' => 3,
            'action_type' => 'email',
            'is_active' => true,
        ]);

        // 2. Create Estimate (3 days old)
        $client = \App\Models\Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'status' => 'sent',
            'nurture_status' => 'active',
        ]);

        // Manual assignment
        $estimate->created_at = $now->copy()->subDays(3);
        $estimate->save();

        // 3. Run Command
        $this->artisan('estimates:nurture')
            ->expectsOutput('Processing Rule: 3 Day Followup')
            ->assertExitCode(0);

        // 4. Assert Notification
        Notification::assertSentTo(
            $client,
            EstimateNurtureNotification::class
        );

        // Assert state updated
        $this->assertNotNull($estimate->fresh()->last_nurtured_at);
    }

    public function test_hot_lead_alert_triggers()
    {
        Notification::fake();

        $creator = User::factory()->create();
        $client = \App\Models\Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'created_by' => $creator->id,
            'client_id' => $client->id,
            'status' => 'sent',
            'estimate_status' => Estimate::EST_STATUS_SENT,
            'client_status' => Estimate::CLT_STATUS_SENT,
            'is_current_version' => true,
            'expires_at' => now()->addDays(7),
        ]);

        // Mock Setting if possible, or use default (3)

        // Simulate 3 views
        $this->get($estimate->public_url)->assertOk(); // 1
        $this->get($estimate->public_url)->assertOk(); // 2
        $this->get($estimate->public_url)->assertOk(); // 3 -> Trigger

        Notification::assertSentTo(
            $creator,
            HotLeadAlert::class
        );

        $this->assertEquals(3, $estimate->fresh()->engagement_score);
    }

    public function test_rescue_service_creates_new_version()
    {
        $estimate = Estimate::factory()->create(['status' => 'sent', 'grand_total' => 100]);

        // Mock Service or resolve real one
        $service = app(\App\Services\RescueNegotiationService::class);

        $newVersion = $service->attemptRescue($estimate, 5); // 5% discount

        $this->assertNotNull($newVersion);
        $this->assertNotEquals($estimate->id, $newVersion->id);
        $this->assertEquals($estimate->estimate_number . '-v2', $newVersion->estimate_number); // assuming factory gave simple number
        $this->assertEquals(5, $newVersion->discount_value);
    }
}
