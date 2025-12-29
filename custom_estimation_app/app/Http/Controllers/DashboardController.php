<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Overview Stats
        $stats = [
            'total' => Estimate::count(),
            'draft' => Estimate::where('status', 'draft')->count(),
            'sent' => Estimate::where('status', 'sent')->count(),
            'accepted' => Estimate::where('status', 'accepted')->count(),
            'declined' => Estimate::where('status', 'declined')->count(),
            'tasks_pending' => \App\Models\Task::pending()->count(),
            'tasks_overdue' => \App\Models\Task::overdue()->count(),
        ];

        // 2. Financials (Pipeline)
        $pipeline_revenue = Estimate::whereIn('status', ['draft', 'sent'])->sum('grand_total');
        $converted_revenue = Estimate::where('status', 'accepted')->sum('grand_total');

        $weightedForecast = Estimate::whereIn('status', ['sent', 'waiting_approval'])
            ->selectRaw('SUM(grand_total * 0.7) as weighted_total')
            ->first()->weighted_total ?? 0;

        // 3. Conversion Rate
        $conversion_rate = 0;
        if ($stats['total'] > 0) {
            $conversion_rate = ($stats['accepted'] / $stats['total']) * 100;
        }

        // 4. Recent Data
        $recent_estimates = Estimate::latest()->take(5)->get();
        $recent_tasks = \App\Models\Task::with('assignedTo')->latest()->take(5)->get();
        $recent_activities = \App\Models\ActivityLog::with('user')->latest()->take(10)->get();

        return view('dashboard', compact(
            'stats',
            'pipeline_revenue',
            'converted_revenue',
            'conversion_rate',
            'weightedForecast',
            'recent_estimates',
            'recent_tasks',
            'recent_activities'
        ));
    }
}
