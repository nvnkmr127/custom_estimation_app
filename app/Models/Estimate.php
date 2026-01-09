<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estimate extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DRAFT = 'draft';

    const STATUS_SENT = 'sent';

    const STATUS_ACCEPTED = 'accepted';

    const STATUS_DECLINED = 'declined';

    const STATUS_EXPIRED = 'expired';

    const STATUS_WAITING_APPROVAL = 'waiting_approval';

    const STATUS_APPROVED = 'approved';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($estimate) {
            if (!$estimate->created_by && auth()->check()) {
                $estimate->created_by = auth()->id();
            }
        });
    }

    protected $fillable = [
        'estimate_number',
        'title',
        'client_id',
        'lead_id',
        'estimate_date',
        'expiry_date',
        'currency',
        'status',
        'type',
        'subtotal',
        'total_tax',
        'discount_total',
        'discount_type',
        'discount_value',
        'grand_total',
        'client_note',
        'admin_note',
        'terms',
        'parent_id',
        'version',
        'is_current_version',
        'signature',
        'signed_at',
        'signer_ip',
        'signer_location',
        'signer_agent',
        'approval_chain_id',
        'approval_status',
        'coupon_code_id',
        'coupon_discount',
        'item_discounts_total',
        'room_discounts_total',
        'perfex_proposal_id',
        'pdf_theme',
        'pdf_template_id',
        'view_count',
        'nudge_task_created',
        'created_by',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent()
    {
        return $this->belongsTo(Estimate::class, 'parent_id');
    }

    public function lead()
    {
        return $this->belongsTo(Client::class, 'lead_id');
    }

    public function versions()
    {
        return $this->hasMany(Estimate::class, 'parent_id')->orderBy('version', 'desc');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current_version', true);
    }

    protected $casts = [
        'estimate_date' => 'date',
        'expiry_date' => 'date',
        'signed_at' => 'datetime',
    ];

    public function sections()
    {
        return $this->hasMany(EstimateSection::class)->orderBy('order_index');
    }

    public function items()
    {
        return $this->hasMany(EstimateItem::class);
    }

    public function getPublicUrlAttribute()
    {
        return \Illuminate\Support\Facades\URL::signedRoute('portal.show', $this);
    }

    public function approvalChain()
    {
        return $this->belongsTo(ApprovalChain::class);
    }

    public function couponCode()
    {
        return $this->belongsTo(CouponCode::class);
    }

    public function comments()
    {
        return $this->hasMany(EstimateComment::class);
    }

    public function unreadComments()
    {
        return $this->comments()->unread()->clientComments();
    }

    public function approvals()
    {
        return $this->hasMany(EstimateApproval::class);
    }

    /**
     * Submit estimate for approval workflow
     */
    /**
     * Submit estimate for approval workflow
     */
    public function submitForApproval()
    {
        if (!$this->approval_chain_id) {
            throw new \Exception('No approval chain assigned to this estimate');
        }

        $this->update(['approval_status' => 'submitted']);

        // Get the first order
        $firstStep = $this->approvalChain->steps()->orderBy('order')->first();

        if ($firstStep) {
            $this->createApprovalsForOrder($firstStep->order);
        }
    }

    /**
     * Create approval records for a specific order step
     */
    public function createApprovalsForOrder($order)
    {
        $steps = $this->approvalChain->steps()->where('order', $order)->get();

        foreach ($steps as $step) {
            $userId = $step->user_id;

            // Check if user is deleted/inactive, use fallback if available
            $user = \App\Models\User::withTrashed()->find($userId);
            if (!$user || $user->trashed()) {
                if ($this->approvalChain->fallback_user_id) {
                    $userId = $this->approvalChain->fallback_user_id;
                } else {
                    // Log warning or handle strictly? For now, skip or log.
                    \Illuminate\Support\Facades\Log::warning("Approval step skipped: User {$userId} inactive and no fallback.", ['estimate_id' => $this->id]);
                    continue;
                }
            }

            // Prevent duplicate pending approvals if fallback user is same as another approver?
            // Simple check:
            $exists = EstimateApproval::where('estimate_id', $this->id)
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->exists();

            if (!$exists) {
                EstimateApproval::create([
                    'estimate_id' => $this->id,
                    'user_id' => $userId,
                    'status' => 'pending',
                ]);
            }
        }
    }

    /**
     * Get the next pending approval step
     */
    /**
     * Get the next pending approval steps (collection)
     */
    public function nextApprovalSteps()
    {
        if (!$this->approvalChain) {
            return collect();
        }

        // Find the current max order that is fully approved
        // This is tricky if orders are skipped or parallel. 
        // Better: Get all approval records. Find the highest order associated with them?
        // No, approval records don't store order.

        // Let's assume sequential orders: 1, 2, 3...
        // We need to find the lowest order that is NOT fully approved yet.

        $chainSteps = $this->approvalChain->steps()->get()->groupBy('order')->sortKeys();

        foreach ($chainSteps as $order => $steps) {
            // Check if this order is fully approved
            $approvedCountForOrder = 0;
            $requiredCount = $steps->count(); // basic logic: all steps in order must approve

            // We need to map approvals back to steps or just check if users approved.
            // Since users can change steps, this is loose.
            // But typically, we check if we have approvals from these users.

            // Improved logic:
            // For this order, check if we have APPROVED records for these users (or fallback).
            // This is getting complex because of fallback users.

            // Alternative: Look at the *active* pending approvals.
            // If there are pending approvals, we are at that step.
            if ($this->approvals()->where('status', 'pending')->exists()) {
                return collect(); // We are currently waiting, no "next" step until these are done.
            }

            // If no pending approvals, check if we have approved records for this order's steps.
            // This assumes we move sequentially.
            // Let's check if *any* step in this order is NOT approved yet.
            // But wait, if they aren't pending and aren't approved, they haven't been created yet?
            // If we strictly follow createApprovalsForOrder, then:

            // 1. Get all approvals for this estimate.
            $approvals = $this->approvals;
            $approvedUserIds = $approvals->where('status', 'approved')->pluck('user_id')->toArray();

            $isOrderComplete = true;
            foreach ($steps as $step) {
                // Logic to determine effective user ID (msg handling fallback)
                // This logic effectively needs to mirror createApprovalsForOrder resolution
                // which is hard.
                // Ideally, we store 'order' on EstimateApproval, but we didn't add that column.

                // Heuristic: If we haven't created approvals for this order yet, then THIS is the next step.
                // How do we know if we created them?
                // We can check if ANY of the users in this order have an approval record (pending or approved).
            }
        }

        // Simplified Logic:
        // We know the current status.
        // We need to find the NEXT order.

        // 1. Find the highest order of the steps that have associated approvals (approved or pending).
        $userIdsWithApprovals = $this->approvals()->pluck('user_id')->toArray();

        $lastTouchedOrder = 0;
        $allSteps = $this->approvalChain->steps()->orderBy('order')->get();

        foreach ($allSteps as $step) {
            // resolve effective user
            $effectiveUserId = $step->user_id;
            $user = \App\Models\User::withTrashed()->find($effectiveUserId);
            if (!$user || $user->trashed()) {
                $effectiveUserId = $this->approvalChain->fallback_user_id;
            }

            if (in_array($effectiveUserId, $userIdsWithApprovals)) {
                $lastTouchedOrder = max($lastTouchedOrder, $step->order);
            }
        }

        // So we are at least at $lastTouchedOrder.
        // Are we done with it?
        $pendingCount = $this->approvals()->where('status', 'pending')->count();
        if ($pendingCount > 0) {
            return collect(); // Still working on current order
        }

        // If no pending, we might be ready for next.
        // Get the first order > lastTouchedOrder
        $nextStepFirst = $this->approvalChain->steps()
            ->where('order', '>', $lastTouchedOrder)
            ->orderBy('order')
            ->first();

        if ($nextStepFirst) {
            return $this->approvalChain->steps()->where('order', $nextStepFirst->order)->get();
        }

        return collect();
    }


    public function isFullyApproved()
    {
        if (!$this->approvalChain) {
            return false;
        }

        // 1. Must ensure no pending approvals exist
        if ($this->approvals()->where('status', 'pending')->exists()) {
            return false;
        }

        // 2. Must ensure no future steps exist
        // If nextApprovalSteps returns items, we are not done (unless they are skipped, which createApprovals handles)
        // Check if we have covered all orders.
        $nextSteps = $this->nextApprovalSteps();

        return $nextSteps->isEmpty();
    }

    public function analytics()
    {
        return $this->hasMany(EstimateAnalytic::class);
    }

    public function pdfTemplate()
    {
        return $this->belongsTo(PdfTemplate::class);
    }

    public function checklistItems()
    {
        return $this->hasMany(EstimateApprovalChecklistItem::class);
    }
    public function getFollowersAttribute()
    {
        // 1. Creator
        $followers = collect();
        if ($this->creator) {
            $followers->push($this->creator);
        }

        // 2. Approvers (users in approval chain who have records)
        // We can get distinct user_id from estimates_approvals
        $approverIds = $this->approvals()->pluck('user_id')->unique();
        $approvers = \App\Models\User::whereIn('id', $approverIds)->get();

        $followers = $followers->merge($approvers);

        // Remove duplicates and self (if logic called by a user, but here we just return list)
        return $followers->unique('id');
    }
}
