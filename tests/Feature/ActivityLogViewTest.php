<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Estimate;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_truncation()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $estimate = Estimate::factory()->create(['created_by' => $user->id]);

        // Create 6 logs
        for ($i = 0; $i < 6; $i++) {
            ActivityLog::create([
                'subject_type' => Estimate::class,
                'subject_id' => $estimate->id,
                'user_id' => $user->id,
                'action' => 'updated',
                'description' => 'Updated estimate'
            ]);
        }

        $response = $this->actingAs($user)->get(route('estimates.show', $estimate));

        $response->assertStatus(200);
        $response->assertSee('View All (6)');
        $response->assertSee('Activity History');
    }

    public function test_activity_log_no_truncation_for_few_items()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $estimate = Estimate::factory()->create(['created_by' => $user->id]);

        // Create 3 logs
        for ($i = 0; $i < 3; $i++) {
            ActivityLog::create([
                'subject_type' => Estimate::class,
                'subject_id' => $estimate->id,
                'user_id' => $user->id,
                'action' => 'updated',
                'description' => 'Updated estimate'
            ]);
        }

        $response = $this->actingAs($user)->get(route('estimates.show', $estimate));

        $response->assertStatus(200);
        $response->assertDontSee('View All');
    }
}
