<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\EstimateApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FollowerSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_follower_creates_manual_follower_and_notifies()
    {
        Notification::fake();
        $creator = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create();
        $estimate = Estimate::factory()->create(['created_by' => $creator->id, 'client_id' => Client::factory()->create()->id]);

        $this->actingAs($creator)->post(route('estimates.followers.add', $estimate), [
            'user_id' => $target->id,
            'permissions' => ['view', 'edit'],
        ]);

        $this->assertTrue($estimate->manualFollowers()->where('user_id', $target->id)->exists());
        Notification::assertSentTo($target, \App\Notifications\EstimateFollowerAdded::class);
    }

    public function test_add_follower_rejects_duplicate()
    {
        $creator = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create();
        $estimate = Estimate::factory()->create(['created_by' => $creator->id, 'client_id' => Client::factory()->create()->id]);
        $estimate->manualFollowers()->create(['user_id' => $target->id, 'permissions' => []]);

        $this->actingAs($creator)->post(route('estimates.followers.add', $estimate), ['user_id' => $target->id]);

        $this->assertEquals(1, $estimate->manualFollowers()->where('user_id', $target->id)->count());
    }

    public function test_followers_list_includes_creator_approver_and_manual_but_only_manual_is_removable()
    {
        $creator = User::factory()->create();
        $approver = User::factory()->create();
        $manual = User::factory()->create();
        $estimate = Estimate::factory()->create(['created_by' => $creator->id, 'client_id' => Client::factory()->create()->id]);

        EstimateApproval::create(['estimate_id' => $estimate->id, 'user_id' => $approver->id, 'status' => 'pending', 'order' => 1]);
        $estimate->manualFollowers()->create(['user_id' => $manual->id, 'permissions' => ['view']]);

        $followerIds = $estimate->followers->pluck('id');
        $this->assertTrue($followerIds->contains($creator->id));
        $this->assertTrue($followerIds->contains($approver->id));
        $this->assertTrue($followerIds->contains($manual->id));

        // The UI's remove condition: only manual followers are removable
        $this->assertTrue($estimate->manualFollowers->contains('user_id', $manual->id));
        $this->assertFalse($estimate->manualFollowers->contains('user_id', $approver->id));
        $this->assertFalse($estimate->manualFollowers->contains('user_id', $creator->id));
    }
}
