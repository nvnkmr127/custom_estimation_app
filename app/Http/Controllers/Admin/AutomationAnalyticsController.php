<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Services\Automation\AnalyticsService;
use Illuminate\Http\Request;

class AutomationAnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get global dashboard analytics.
     */
    public function dashboard(Request $request)
    {
        $days = $request->input('days', 30);
        $stats = $this->analyticsService->getDashboardStats($days);

        return response()->json($stats);
    }

    /**
     * Get analytics for a specific automation.
     */
    public function show(Request $request, Automation $automation)
    {
        $days = $request->input('days', 30);
        $stats = $this->analyticsService->getAutomationStats($automation->id, $days);

        return response()->json($stats);
    }
}
