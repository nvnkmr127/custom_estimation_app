<?php

namespace App\Listeners;

use App\Core\Events\Estimates\EstimateViewed;
use App\Models\ActivityLog;
use App\Models\Estimate;
use App\Models\Setting;
use App\Notifications\HotLeadAlert;
use App\Services\PerfexApiService;
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

    public function __construct(protected PerfexApiService $perfexApi)
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
        $this->handleSmartNudge($estimate);
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

    protected function handleSmartNudge(Estimate $estimate): void
    {
        $viewCount = $estimate->view_count;
        $nudgeThreshold = config('estimation.nudge_threshold', 3);

        if (
            $viewCount >= $nudgeThreshold
            && !in_array($estimate->status, [Estimate::STATUS_ACCEPTED, Estimate::STATUS_DECLINED, Estimate::STATUS_EXPIRED])
            && !$estimate->nudge_task_created
            && $estimate->perfex_proposal_id
        ) {
            try {
                $description = "Smart Nudge: Client has viewed Estimate #{$estimate->estimate_number} {$viewCount} times on the portal but not accepted.";
                $taskData = [
                    'name' => 'Follow up on Estimate #' . $estimate->estimate_number,
                    'description' => $description,
                    'priority' => 3,
                    'startdate' => now()->format('Y-m-d'),
                    'rel_type' => 'proposal',
                    'rel_id' => $estimate->perfex_proposal_id,
                ];

                $response = $this->perfexApi->createTask($taskData);

                if (isset($response['id']) || (isset($response['status']) && $response['status'] == true)) {
                    $estimate->update(['nudge_task_created' => true]);
                    ActivityLog::log('system_action', $estimate, 'Smart Nudge: CRM Task Created for follow-up.');
                }
            } catch (\Exception $e) {
                Log::error("Intelligence: Failed to create smart nudge task for Estimate #{$estimate->estimate_number}: " . $e->getMessage());
            }
        }
    }
}
