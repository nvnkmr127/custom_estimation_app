<?php

namespace Tests\Feature;

use App\Jobs\GenerateBatchPdfZip;
use App\Models\Estimate;
use App\Models\PdfTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PdfPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_download_dispatches_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $estimates = Estimate::factory()->count(3)->create();

        $this->actingAs($user)
            ->post(route('estimates.batch-download'), [
                'estimate_ids' => $estimates->pluck('id')->toArray(),
            ]);

        Queue::assertPushed(GenerateBatchPdfZip::class);
    }

    public function test_custom_template_pdf_is_cached()
    {
        $user = User::factory()->create();
        $template = PdfTemplate::factory()->create(['html_content' => '<h1>Hello</h1>']);
        $estimate = Estimate::factory()->create(['pdf_template_id' => $template->id]);

        $this->actingAs($user)
            ->get(route('estimates.pdf', $estimate))
            ->assertStatus(200);

        // Check if file exists in cache directory
        // We can't easily predict the exact filename because of timestamps,
        // but we can check if *any* file exists or perform a second request and assert speed/logic if we mocked Service.
        // For integration test, verifying 200 OK is good base, and we manually verify cache file creation.

        // Ensure directory exists for test
        if (! file_exists(storage_path('app/public/estimates_cache'))) {
            mkdir(storage_path('app/public/estimates_cache'), 0755, true);
        }

        // Verify file creation
        $cacheKey = "estimate_{$estimate->id}_v{$estimate->updated_at->timestamp}_tpl{$template->id}_v{$template->updated_at->timestamp}.pdf";
        $cachePath = storage_path('app/public/estimates_cache/'.$cacheKey);

        $this->assertFileExists($cachePath, 'Cache file should be created');

        // Cleanup
        @unlink($cachePath);
    }
}
