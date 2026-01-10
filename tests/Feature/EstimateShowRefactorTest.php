<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Estimate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateShowRefactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimate_show_page_receives_correct_view_data()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $otherUser = User::factory()->create(['role' => 'estimator']);

        $estimate = Estimate::factory()->create([
            'estimate_number' => 'EST-VIEW-TEST',
            'title' => 'Refactor Test',
            'created_by' => $user->id,
            'client_id' => 1 // simplified
        ]);

        $response = $this->actingAs($user)->get(route('estimates.show', $estimate));

        $response->assertStatus(200);
        $response->assertViewIs('estimates.show');

        // Assert view receives the new variables
        $response->assertViewHas('latestVersion');
        $response->assertViewHas('potentialFollowers');

        // Verify potential followers logic (should not include creator)
        $potentialFollowers = $response->viewData('potentialFollowers');
        $this->assertTrue($potentialFollowers->contains($otherUser));
        $this->assertFalse($potentialFollowers->contains($user));
    }
}
