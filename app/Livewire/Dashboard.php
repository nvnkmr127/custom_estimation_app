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

        return [
            'total_value' => $totalVal,
            'win_rate' => $winRate,
            'avg_deal_size' => $avgDealSize,
            'avg_approval_time' => $avgApprovalTime,
        ];
    }
}
