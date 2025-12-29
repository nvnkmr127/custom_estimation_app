<?php

namespace Tests\Feature;

use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BatchDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_download_batch_estimates()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $estimates = Estimate::factory()->count(3)->create([
            'client_id' => \App\Models\Client::factory()->create()->id
        ]);

        $response = $this->actingAs($user)
            ->post(route('estimates.batch-download'), [
                'estimate_ids' => $estimates->pluck('id')->toArray()
            ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');

        // Verify content is a valid ZIP
        $this->assertTrue($response->baseResponse instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse);
        $filePath = $response->baseResponse->getFile()->getPathname();

        $zip = new ZipArchive;
        $res = $zip->open($filePath);
        $this->assertTrue($res === TRUE, "Failed to open generated ZIP");

        $this->assertEquals(3, $zip->numFiles);
        $zip->close();
    }
}
