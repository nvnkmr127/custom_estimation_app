<?php

namespace App\Jobs;

use App\Models\WebhookInboundEvent;
use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInboundWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected WebhookInboundEvent $event
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->event->status !== 'pending') {
            return;
        }

        try {
            $this->processByProvider();

            $this->event->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Webhook: Failed to process event #{$this->event->id}: " . $e->getMessage());

            $this->event->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function processByProvider(): void
    {
        match ($this->event->provider) {
            'perfex' => $this->handlePerfexEvent(),
            default => Log::info("Webhook: No specific handler for provider {$this->event->provider}"),
        };
    }

    protected function handlePerfexEvent(): void
    {
        $payload = $this->event->payload;
        $type = $payload['event_type'] ?? $payload['action'] ?? null;
        $id = $payload['proposal_id'] ?? $payload['id'] ?? null;

        if (!$id)
            return;

        if ($type === 'proposal_accepted') {
            Estimate::where('perfex_proposal_id', $id)->update(['status' => 'accepted']);
        } elseif ($type === 'proposal_declined') {
            Estimate::where('perfex_proposal_id', $id)->update(['status' => 'declined']);
        }
    }
}
