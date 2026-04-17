<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Estimate;
use App\Models\EstimateApproval;
use App\Models\ActivityLog;
use App\Models\Task;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Dashboard extends Component
{
    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();
        $cacheDuration = 300; // 5 minutes

        // 1. Identify Role Context
        $roles = [
            'is_admin' => $user->isAdmin(),
            'is_approver' => $user->hasRole(['estimator_manager', 'super_admin', 'estimator_admin']),
            'is_sales' => $user->hasRole(['estimator', 'estimator_manager']),
        ];

        // 2. Fetch "Next Actions"
        $nextActions = $this->getNextActions($user);

        // 3. Fetch Pipeline Stats (Count & Value)
        $pipeline = $this->getPipelineStats($user);

        // 4. Fetch Smart Alerts
        $alerts = $this->getSmartAlerts($user);

        // 5. Performance Metrics
        $metrics = $this->getPerformanceMetrics($user);

        // 7. Get Currency Symbol
        $currencySymbol = \App\Models\Setting::getCurrencySymbol();

        // 8. Recent Data (Restored metrics)
        $recent_estimates = Estimate::query()
            ->with(['client', 'creator'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('created_by', $user->id))
            ->latest()
            ->take(5)
            ->get();

        $hot_leads = Estimate::query()
            ->with('client')
            ->where('estimate_status', Estimate::EST_STATUS_SENT)
            ->when(!$user->isAdmin(), fn($q) => $q->where('created_by', $user->id))
            ->where(fn($q) => $q->where('engagement_score', '>', 0)->orWhere('view_count', '>', 1))
            ->orderByDesc('engagement_score')
            ->orderByDesc('last_viewed_at')
            ->take(5)
            ->get();

        $recent_tasks = \App\Models\Task::with('assignedTo')
            ->when(!$user->isAdmin(), fn($q) => $q->where('assigned_to', $user->id))
            ->latest()
            ->take(5)
            ->get();

        $recent_activities = \App\Models\ActivityLog::with('user')
            ->when(!$user->isAdmin(), fn($q) => $q->where('user_id', $user->id))
            ->latest()
            ->take(10)
            ->get();

        $expiring_estimates = Estimate::query()
            ->with(['client', 'creator'])
            ->where('estimate_status', Estimate::EST_STATUS_SENT)
            ->when(!$user->isAdmin(), fn($q) => $q->where('created_by', $user->id))
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->orderBy('expires_at')
            ->take(5)
            ->get();

        return view('livewire.dashboard', [
            'roles' => $roles,
            'nextActions' => $nextActions,
            'pipeline' => $pipeline,
            'alerts' => $alerts,
            'metrics' => $metrics,
            'currencySymbol' => $currencySymbol,
            'recent_estimates' => $recent_estimates,
            'hot_leads' => $hot_leads,
            'recent_tasks' => $recent_tasks,
            'recent_activities' => $recent_activities,
            'expiring_estimates' => $expiring_estimates,
        ]);
    }

    protected function getNextActions($user)
    {
        $actions = [];

        // 1. Approvals Needed (Current user is the designated approver)
        $pendingApprovals = EstimateApproval::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
        if ($pendingApprovals > 0) {
            $actions[] = [
                'id' => 'approvals',
                'label' => "{$pendingApprovals} estimates need your approval",
                'count' => $pendingApprovals,
                'color' => 'amber',
                'url' => route('estimates.index', ['status' => 'pending_approval', 'approver' => $user->id]),
            ];
        }

        // 2. Ready to Send (Approved but not yet sent to client)
        $readyToSend = Estimate::where('created_by', $user->id)
            ->where('estimate_status', Estimate::EST_STATUS_APPROVED)
            ->where('client_status', Estimate::CLT_STATUS_NOT_SENT)
            ->count();
        if ($readyToSend > 0) {
            $actions[] = [
                'id' => 'ready_to_send',
                'label' => "{$readyToSend} estimates ready to send",
                'count' => $readyToSend,
                'color' => 'indigo',
                'url' => route('estimates.index', ['status' => 'approved', 'client_status' => 'not_sent']),
            ];
        }

        // 3. Rejected / Resubmit (Rejected by internal approval)
        $rejectedCount = Estimate::where('created_by', $user->id)
            ->where('approval_status', Estimate::APP_STATUS_REJECTED)
            ->count();
        if ($rejectedCount > 0) {
            $actions[] = [
                'id' => 'rejected',
                'label' => "{$rejectedCount} estimates rejected – resubmit",
                'count' => $rejectedCount,
                'color' => 'rose',
                'url' => route('estimates.index', ['approval_status' => 'rejected']),
            ];
        }

        // 4. Smart Nudge: Impending Expirations (Expiring within 48 hours)
        $expiringSoon = Estimate::where('created_by', $user->id)
            ->where('estimate_status', Estimate::EST_STATUS_SENT)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<', now()->addHours(48))
            ->count();
        if ($expiringSoon > 0) {
            $actions[] = [
                'id' => 'expiring',
                'label' => "{$expiringSoon} estimates expiring soon – send reminder",
                'count' => $expiringSoon,
                'color' => 'orange',
                'url' => route('estimates.index', ['status' => 'sent', 'expiring' => 1]),
            ];
        }

        return $actions;
    }

    protected function getPipelineStats($user)
    {
        $statuses = [
            Estimate::EST_STATUS_DRAFT => 'Draft',
            Estimate::EST_STATUS_PENDING_APPROVAL => 'In Approval',
            Estimate::EST_STATUS_APPROVED => 'Approved',
            Estimate::EST_STATUS_SENT => 'Sent',
            Estimate::EST_STATUS_ACCEPTED => 'Accepted',
            Estimate::EST_STATUS_DECLINED => 'Declined',
            Estimate::EST_STATUS_EXPIRED => 'Expired',
        ];

        $stats = [];
        foreach ($statuses as $status => $label) {
            $query = Estimate::where('estimate_status', $status);
            if (!$user->isAdmin()) {
                $query->where('created_by', $user->id);
            }

            $stats[$status] = [
                'label' => $label,
                'count' => $query->count(),
                'value' => $query->sum('grand_total'),
            ];
        }

        return $stats;
    }

    protected function getSmartAlerts($user)
    {
        $alerts = [];

        // 1. High Discount (>20%) - Drafts or Pending Approval
        $highDiscounts = Estimate::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('created_by', $user->id))
            ->whereIn('estimate_status', [Estimate::EST_STATUS_DRAFT, Estimate::EST_STATUS_PENDING_APPROVAL])
            ->where(function($q) {
                $q->whereRaw('CASE WHEN subtotal > 0 THEN (discount_total / subtotal) ELSE 0 END > 0.20');
            })
            ->count();
        if ($highDiscounts > 0) {
            $alerts[] = [
                'label' => 'High discount (>20%) estimates',
                'count' => $highDiscounts,
                'type' => 'warning',
            ];
        }

        // 2. Delayed Approval (>48h) - Estimates stuck in pending_approval
        $delayedApprovals = Estimate::where('estimate_status', Estimate::EST_STATUS_PENDING_APPROVAL)
            ->when(!$user->isAdmin(), fn($q) => $q->where('created_by', $user->id))
            ->where('updated_at', '<', now()->subHours(48))
            ->count();
        if ($delayedApprovals > 0) {
            $alerts[] = [
                'label' => 'Approval pending > 48 hours',
                'count' => $delayedApprovals,
                'type' => 'danger',
            ];
        }

        // 3. Client hasn't viewed estimate within 24h of sending
        $notViewed = Estimate::where('estimate_status', Estimate::EST_STATUS_SENT)
            ->when(!$user->isAdmin(), fn($q) => $q->where('created_by', $user->id))
            ->where('view_count', 0)
            ->where('sent_at', '<', now()->subHours(24))
            ->count();
        if ($notViewed > 0) {
            $alerts[] = [
                'label' => 'Client hasn’t viewed estimate',
                'count' => $notViewed,
                'type' => 'info',
            ];
        }

        // 4. Expiring Soon (Next 7 days)
        $expiringNextSeven = Estimate::where('estimate_status', Estimate::EST_STATUS_SENT)
            ->when(!$user->isAdmin(), fn($q) => $q->where('created_by', $user->id))
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<', now()->addDays(7))
            ->count();
        if ($expiringNextSeven > 0) {
            $alerts[] = [
                'label' => 'Estimates expiring in 7 days',
                'count' => $expiringNextSeven,
                'type' => 'warning',
            ];
        }

        return $alerts;
    }

    protected function getPerformanceMetrics($user)
    {
        $thisMonth = [now()->startOfMonth(), now()->endOfMonth()];
        
        $baseQuery = Estimate::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('created_by', $user->id));

        // Value of all estimates created this month
        $totalVal = (clone $baseQuery)->whereBetween('created_at', $thisMonth)->sum('grand_total');
        
        // Conversion: Accepted / Sent (ever)
        $sentCount = (clone $baseQuery)->where('estimate_status', Estimate::EST_STATUS_SENT)->count();
        $acceptedCount = (clone $baseQuery)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->count();
        $winRate = $sentCount > 0 ? ($acceptedCount / ($sentCount + $acceptedCount)) * 100 : 0;

        // Avg Deal Size for Accepted estimates
        $avgDealSize = $acceptedCount > 0 
            ? (clone $baseQuery)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->avg('grand_total') 
            : 0;

        // Avg Approval Time - Placeholder: In a real system we would calculate the difference between creation and approval
        $avgApprovalTime = "1.4 days"; 

        // New Logic: Revenue Forecast (Sent Value * Win Rate)
        $sentValue = (clone $baseQuery)->where('estimate_status', Estimate::EST_STATUS_SENT)->sum('grand_total');
        $forecastValue = $sentValue * ($winRate / 100);

        // 1. Team Leaderboard (For Admins/Managers)
        $leaderboard = [];
        if ($user->isAdmin() || $user->hasRole('estimator_manager')) {
            $leaderboard = \App\Models\User::query()
                ->select('users.id', 'users.name')
                ->withCount(['estimates as won_count' => function($q) {
                    $q->where('estimate_status', Estimate::EST_STATUS_ACCEPTED);
                }])
                ->withSum(['estimates as total_revenue' => function($q) {
                    $q->where('estimate_status', Estimate::EST_STATUS_ACCEPTED);
                }], 'grand_total')
                ->orderByDesc('total_revenue')
                ->take(3)
                ->get();
        }

        // 2. Operational Bottlenecks (For Admins)
        $bottlenecks = [];
        if ($user->isAdmin() || $user->hasRole('estimator_manager')) {
            $bottlenecks = EstimateApproval::where('status', 'pending')
                ->where('created_at', '<', now()->subHours(24))
                ->join('users', 'estimate_approvals.user_id', '=', 'users.id')
                ->select('users.name', DB::raw('count(*) as pending_count'))
                ->groupBy('users.name')
                ->orderByDesc('pending_count')
                ->take(3)
                ->get();
        }

        // New Logic: Engagement Heatmap (Clients with highest view velocity)
        $clientEngagement = Estimate::current()
            ->with('client')
            ->where('view_count', '>', 0)
            ->when(!$user->isAdmin(), fn($q) => $q->where('created_by', $user->id))
            ->select('client_id', DB::raw('SUM(view_count) as total_views'))
            ->groupBy('client_id')
            ->orderByDesc('total_views')
            ->take(5)
            ->get();

        // 3. Personal Achievement (Current Month vs. Dynamic Target)
        $thisMonthRange = [now()->startOfMonth(), now()->endOfMonth()];
        $lastMonthRange = [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];

        $currentProgress = (clone $baseQuery)
            ->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)
            ->whereBetween('updated_at', $thisMonthRange)
            ->sum('grand_total');

        $lastMonthRevenue = (clone $baseQuery)
            ->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)
            ->whereBetween('updated_at', $lastMonthRange)
            ->sum('grand_total');

        // Target: 15% growth over last month, min 10k
        $targetRevenue = max(10000, $lastMonthRevenue * 1.15);
        $progressPercent = $targetRevenue > 0 ? (min($currentProgress, $targetRevenue) / $targetRevenue) * 100 : 0;

        return [
            'total_value' => $totalVal,
            'win_rate' => $winRate,
            'avg_deal_size' => $avgDealSize,
            'avg_approval_time' => $avgApprovalTime,
            'forecast_value' => $forecastValue,
            'leaderboard' => $leaderboard,
            'bottlenecks' => $bottlenecks,
            'client_engagement' => $clientEngagement,
            'personal_achievement' => [
                'current' => $currentProgress,
                'target' => $targetRevenue,
                'percent' => $progressPercent,
            ],
        ];
    }
}
