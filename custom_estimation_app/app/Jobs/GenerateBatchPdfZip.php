<?php

namespace App\Jobs;

use App\Models\Estimate;
use App\Models\User;
use App\Services\PdfRenderingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class GenerateBatchPdfZip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $estimateIds;

    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $estimateIds, int $userId)
    {
        $this->estimateIds = $estimateIds;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $estimates = Estimate::whereIn('id', $this->estimateIds)->get();
        if ($estimates->isEmpty()) {
            return;
        }

        $zipFileName = 'estimates_batch_'.now()->format('YmdHis').'_'.\Illuminate\Support\Str::random(8).'.zip';
        $zipPath = storage_path('app/public/batch_exports/'.$zipFileName);

        // Ensure directory exists
        if (! file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            $pdfService = new PdfRenderingService;
            $settings = \App\Models\Setting::pluck('value', 'key');

            foreach ($estimates as $estimate) {
                // Ensure relationships are loaded
                $estimate->load(['sections.items', 'items', 'client']);

                $pdfContent = null;
                $filename = 'estimate_'.$estimate->estimate_number.'.pdf';

                try {
                    // Try to use cached version if custom template
                    if ($estimate->pdf_template_id && $estimate->pdfTemplate) {
                        $template = $estimate->pdfTemplate;
                        // Use the new caching service
                        $cachedPath = $pdfService->renderAndCache($template, $estimate);
                        if ($cachedPath && file_exists($cachedPath)) {
                            $zip->addFile($cachedPath, $filename);
                        }
                    } else {
                        // Standard fallback (non-cached for now as we didn't implement cache for blade views yet)
                        // Or we can just render on fly
                        $theme = $estimate->pdf_theme ?: ($settings['pdf_theme'] ?? 'modern');
                        $view = 'estimates.print_'.$theme;
                        if (! view()->exists($view)) {
                            $view = 'estimates.print_modern';
                        }

                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, compact('estimate', 'settings'));
                        $pdf->setPaper('a4', 'portrait');
                        $zip->addFromString($filename, $pdf->output());
                    }
                } catch (\Exception $e) {
                    Log::error("Batch ZIP: Failed to add estimate #{$estimate->id}: ".$e->getMessage());
                    $zip->addFromString('error_log.txt', "Failed to generate estimate #{$estimate->estimate_number}: ".$e->getMessage()."\n");
                }
            }

            $zip->close();

            // Notify User
            $downloadUrl = asset('storage/batch_exports/'.$zipFileName);
            $user->notify(new \App\Notifications\BatchExportReady($downloadUrl));

        } else {
            Log::error("Batch ZIP: Failed to create zip file at $zipPath");
        }
    }
}
