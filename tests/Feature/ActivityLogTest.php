<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function seedLog(string $action, string $subjectType = Estimate::class, ?int $userId = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => 1,
            'description' => "Test {$action}",
        ]);
    }

    public function test_index_lists_logs_and_filter_dropdown_reflects_real_actions()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->seedLog('version_approved');
        $this->seedLog('status_updated', \App\Models\User::class);

        $response = $this->actingAs($admin)->get(route('activities.index'));

        $response->assertOk();
        // Real logged actions appear as options (humanized)
        $response->assertSee('Version approved');
        $response->assertSee('Status updated');
        // The old hardcoded options that were never logged must NOT appear
        $response->assertDontSee('>Accepted</option>', false);
        // Subject types are data-driven too (option value carries the real class)
        $response->assertSee('value="App\Models\User"', false);
    }

    public function test_action_filter_returns_matching_rows()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $match = $this->seedLog('version_approved');
        $other = $this->seedLog('status_updated');

        $response = $this->actingAs($admin)->get(route('activities.index', ['action' => 'version_approved']));

        $response->assertOk();
        $response->assertSee('Test version_approved');
        $response->assertDontSee('Test status_updated');
    }

    public function test_show_renders_for_a_system_log_with_no_user()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $log = $this->seedLog('sales_nudge_sent', Estimate::class, null); // system log, user_id null

        $this->actingAs($admin)->get(route('activities.show', $log))->assertOk();
    }

    public function test_non_admin_cannot_access_activity_log()
    {
        $estimator = User::factory()->create(['role' => 'estimator']);
        $this->actingAs($estimator)->get(route('activities.index'))->assertForbidden();
    }
}
