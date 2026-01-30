<?php

namespace App\Listeners;

use App\Core\Events\DomainEvent;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ConversionTrackingListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DomainEvent $event): void
    {
        // We only care about "Accepted/Approved" events for conversion
        if ($event->getEventName() !== 'estimate.approved') {
            return;
        }

        $entityId = $event->getEntityId();
        $entityType = $event->getEntityType();

        if ($entityType !== 'estimate') {
            return;
        }

        // Find the most recent email log for this estimate
        $log = EmailLog::where('entity_type', 'estimate')
            ->where('entity_id', $entityId)
            ->whereNull('converted_at')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($log) {
            $convertedAt = now();
            $conversionTime = $convertedAt->diffInSeconds($log->created_at);

            $log->update([
                'converted_at' => $convertedAt,
                'conversion_time_seconds' => $conversionTime,
            ]);

            Log::info("Conversion tracked for Estimate #{$entityId}. Time: {$conversionTime}s");
        }
    }
}
