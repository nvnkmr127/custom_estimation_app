<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Estimate;
use Livewire\Attributes\Layout;

class Dashboard extends Component
{
    #[Layout('layouts.app')]
    public function render()
    {
        $cacheDuration = 300; // 5 minutes
        $user = auth()->user();

        $stats = \Illuminate\Support\Facades\Cache::remember('dashboard_stats_' . $user->id, $cacheDuration, function () use ($user) {
            $estimateQuery = Estimate::query();
            $taskQuery = \App\Models\Task::query();

            // Scope for non-admins
            if (!$user->isAdmin()) {
                $estimateQuery->where('created_by', $user->id);
                $taskQuery->where('assigned_to', $user->id);
            }

            // 1. Overview Stats
            return [
                'total' => (clone $estimateQuery)->count(),
                'draft' => (clone $estimateQuery)->where('estimate_status', Estimate::EST_STATUS_DRAFT)->count(),
                'sent' => (clone $estimateQuery)->where('estimate_status', Estimate::EST_STATUS_SENT)->count(),
                'accepted' => (clone $estimateQuery)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->count(),
                'declined' => (clone $estimateQuery)->where('estimate_status', Estimate::EST_STATUS_DECLINED)->count(),
                'tasks_pending' => (clone $taskQuery)->pending()->count(),
                'tasks_overdue' => (clone $taskQuery)->overdue()->count(),
            ];
        });

        // 2. Financials (Pipeline)
        $financials = \Illuminate\Support\Facades\Cache::remember('dashboard_financials_' . $user->id, $cacheDuration, function () use ($user) {
            $estimateQuery = Estimate::query();

            if (!$user->isAdmin()) {
                $estimateQuery->where('created_by', $user->id);
            }

            $weightedResult = (clone $estimateQuery)->whereIn('estimate_status', [Estimate::EST_STATUS_SENT, Estimate::EST_STATUS_PENDING_APPROVAL])
                ->selectRaw('SUM(grand_total * 0.7) as weighted_total')
                ->first();

            return [
                'pipeline_revenue' => (clone $estimateQuery)->whereIn('estimate_status', [Estimate::EST_STATUS_DRAFT, Estimate::EST_STATUS_SENT, Estimate::EST_STATUS_PENDING_APPROVAL, Estimate::EST_STATUS_APPROVED])->sum('grand_total'),
                'converted_revenue' => (clone $estimateQuery)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->sum('grand_total'),
                'weightedForecast' => $weightedResult ? $weightedResult->weighted_total : 0,
            ];
        });

        $pipeline_revenue = $financials['pipeline_revenue'];
        $converted_revenue = $financials['converted_revenue'];
        $weightedForecast = $financials['weightedForecast'];

        // 3. Conversion Rate
        $conversion_rate = 0;
        if ($stats['total'] > 0) {
            $conversion_rate = ($stats['accepted'] / $stats['total']) * 100;
        }

        // 4. Recent Data
        // Base Query Reuse
        $baseEstimateQuery = Estimate::query();
        if (!$user->isAdmin()) {
            $baseEstimateQuery->where('created_by', $user->id);
        }

        $recent_estimates = (clone $baseEstimateQuery)->latest()->take(5)->get();

        // Hot Leads: Sent estimates with engagement > 0 or multiple views
        $hot_leads = (clone $baseEstimateQuery)->where('estimate_status', Estimate::EST_STATUS_SENT)
            ->where(function ($q) {
                $q->where('engagement_score', '>', 0)
                  ->orWhere('view_count', '>', 1);
            })
            ->orderByDesc('engagement_score')
            ->orderByDesc('last_viewed_at')
            ->take(5)
            ->get();

        $recentQuery = \App\Models\Task::with('assignedTo');
        if (!$user->isAdmin()) {
            $recentQuery->where('assigned_to', $user->id);
        }
        $recent_tasks = $recentQuery->latest()->take(5)->get();

        // Activity Logs
        // Admin sees all, User sees their own actions? Or actions on their estimates?
        // Simplest: User sees actions they performed.
        $activityQuery = \App\Models\ActivityLog::with('user');
        if (!$user->isAdmin()) {
            $activityQuery->where('user_id', $user->id);
        }
        $recent_activities = $activityQuery->latest()->take(10)->get();

        return view('livewire.dashboard', compact(
            'stats',
            'pipeline_revenue',
            'converted_revenue',
            'conversion_rate',
            'weightedForecast',
            'recent_estimates',
            'hot_leads',
            'recent_tasks',
            'recent_activities'
        ));
    }
}
