<?php

namespace App\Listeners;

use App\Core\Events\Estimates\EstimateViewed;
use App\Models\ActivityLog;
use App\Models\Estimate;
use App\Models\Setting;
use App\Notifications\HotLeadAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class EstimateIntelligenceListener implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public $backoff = 60;

    public function __construct()
    {
    }

    public function handle(\App\Core\Events\DomainEvent $event): void
    {
        if ($event->getEventName() !== 'estimate.viewed') {
            return;
        }

        // We expect EstimateViewed event
        if (!$event instanceof \App\Core\Events\Estimates\EstimateViewed) {
            return;
        }

        $estimate = $event->estimate;

        // Skip internal views
        if ($event->viewerId && $estimate->created_by === $event->viewerId) {
            return;
        }

        $this->handleHotLeadDetection($estimate);
    }

    protected function handleHotLeadDetection(Estimate $estimate): void
    {
        $threshold = Setting::getCached('nurture_hot_lead_threshold', 3);

        // Count views in last hour
        $recentViews = ActivityLog::where('subject_type', Estimate::class)
            ->where('subject_id', $estimate->id)
            ->where('action', 'proposal_viewed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentViews >= $threshold) {
            $cacheKey = "hot_lead_alerted_{$estimate->id}";
            if (!Cache::has($cacheKey)) {
                $followers = $estimate->followers;
                Notification::send($followers, new HotLeadAlert($estimate, [
                    'reason' => "High Velocity: Viewed {$recentViews} times in the last hour."
                ]));

                Cache::put($cacheKey, true, now()->addHour());
                Log::info("Intelligence: Hot lead alert sent for Estimate #{$estimate->estimate_number}");
            }
        }
    }

}
