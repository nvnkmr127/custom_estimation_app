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

        $stats = \Illuminate\Support\Facades\Cache::remember('dashboard_stats_' . auth()->id(), $cacheDuration, function () {
            // 1. Overview Stats
            return [
                'total' => Estimate::count(),
                'draft' => Estimate::where('status', 'draft')->count(),
                'sent' => Estimate::where('status', 'sent')->count(),
                'accepted' => Estimate::where('status', 'accepted')->count(),
                'declined' => Estimate::where('status', 'declined')->count(),
                'tasks_pending' => \App\Models\Task::pending()->count(),
                'tasks_overdue' => \App\Models\Task::overdue()->count(),
            ];
        });

        // 2. Financials (Pipeline)
        $financials = \Illuminate\Support\Facades\Cache::remember('dashboard_financials_' . auth()->id(), $cacheDuration, function () {
            return [
                'pipeline_revenue' => Estimate::whereIn('status', ['draft', 'sent'])->sum('grand_total'),
                'converted_revenue' => Estimate::where('status', 'accepted')->sum('grand_total'),
                'weightedForecast' => Estimate::whereIn('status', ['sent', 'waiting_approval'])
                    ->selectRaw('SUM(grand_total * 0.7) as weighted_total')
                    ->first()->weighted_total ?? 0,
            ];
        });

        $pipeline_revenue = $financials['pipeline_revenue'];
        $converted_revenue = $financials['converted_revenue'];
        $weightedForecast = $financials['weighted_total'] ?? $financials['weightedForecast']; // accommodate selectRaw alias

        // 3. Conversion Rate
        $conversion_rate = 0;
        if ($stats['total'] > 0) {
            $conversion_rate = ($stats['accepted'] / $stats['total']) * 100;
        }

        // 4. Recent Data
        $recent_estimates = Estimate::latest()->take(5)->get();
        // Hot Leads: Sent estimates with engagement > 0, ordered by score
        $hot_leads = Estimate::where('status', 'sent')
            ->where('engagement_score', '>', 0)
            ->orderByDesc('engagement_score')
            ->orderByDesc('last_viewed_at')
            ->take(5)
            ->get();

        $recent_tasks = \App\Models\Task::with('assignedTo')->latest()->take(5)->get();
        $recent_activities = \App\Models\ActivityLog::with('user')->latest()->take(10)->get();

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
