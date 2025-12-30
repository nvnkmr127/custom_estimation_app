<?php

namespace Tests\Feature;

use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_download_batch_estimates()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $estimates = Estimate::factory()->count(3)->create([
            'client_id' => \App\Models\Client::factory()->create()->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('estimates.batch-download'), [
                'estimate_ids' => $estimates->pluck('id')->toArray(),
            ]);

        $response->assertStatus(302); // Redirect back
        $response->assertSessionHas('success', 'Batch export started. You will receive an email/notification when it is ready.');
    }
}
