<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Setting;

class Estimate extends Model
{
    use HasFactory, SoftDeletes;

    // Structured Statuses
    // Internal Estimate Lifecycle (Main Status)
    const EST_STATUS_DRAFT = 'draft';
    const EST_STATUS_PENDING_APPROVAL = 'pending_approval';
    const EST_STATUS_APPROVED = 'approved';
    const EST_STATUS_SENT = 'sent';
    const EST_STATUS_ACCEPTED = 'accepted';
    const EST_STATUS_DECLINED = 'declined';
    const EST_STATUS_EXPIRED = 'expired';

    // Approval Workflow Status
    const APP_STATUS_NOT_REQUIRED = 'not_required';
    const APP_STATUS_WAITING = 'waiting';
    const APP_STATUS_APPROVED = 'approved';
    const APP_STATUS_CHANGES_REQUESTED = 'changes_requested';
    const APP_STATUS_REJECTED = 'rejected';

    // Client Interaction Status
    const CLT_STATUS_NOT_SENT = 'not_sent';
    const CLT_STATUS_SENT = 'sent';
    const CLT_STATUS_VIEWED = 'viewed';
    const CLT_STATUS_ACCEPTED = 'accepted';
    const CLT_STATUS_DECLINED = 'declined';
    const CLT_STATUS_EXPIRED = 'expired';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($estimate) {
            if (!$estimate->created_by && auth()->check()) {
                $estimate->created_by = auth()->id();
            }

            // Apply defaults if not set
            $estimate->estimate_status = $estimate->estimate_status ?? self::EST_STATUS_DRAFT;
            $estimate->approval_status = $estimate->approval_status ?? self::APP_STATUS_NOT_REQUIRED;
            $estimate->client_status = $estimate->client_status ?? self::CLT_STATUS_NOT_SENT;
        });

        static::saving(function ($estimate) {
            // Gracefully migrate legacy 'active' status on save
            $legacyMap = [
                'draft' => self::EST_STATUS_DRAFT,
                'sent' => self::EST_STATUS_SENT,
                'accepted' => self::EST_STATUS_ACCEPTED,
                'declined' => self::EST_STATUS_DECLINED,
                'expired' => self::EST_STATUS_EXPIRED,
            ];

            if (
                $estimate->isDirty('status')
                && !$estimate->isDirty('estimate_status')
                && $estimate->status
                && in_array($estimate->status, array_keys($legacyMap))
            ) {
                $estimate->estimate_status = $legacyMap[$estimate->status] ?? self::EST_STATUS_DRAFT;
            }

            // Ensure client_status is NEVER 'draft' (DB default is 'draft' which is wrong for the enum)
            if ($estimate->client_status === 'draft' || !$estimate->client_status) {
                $estimate->client_status = self::CLT_STATUS_NOT_SENT;
            }

            // Ensure approval_status is NEVER 'draft'
            if ($estimate->approval_status === 'draft' || !$estimate->approval_status) {
                $estimate->approval_status = self::APP_STATUS_NOT_REQUIRED;
            }

            if ($estimate->approval_status === 'submitted') {
                $estimate->approval_status = self::APP_STATUS_WAITING;
            }

            // Enum validation
            $valid_est = [
                self::EST_STATUS_DRAFT,
                self::EST_STATUS_PENDING_APPROVAL,
                self::EST_STATUS_APPROVED,
                self::EST_STATUS_SENT,
                self::EST_STATUS_ACCEPTED,
                self::EST_STATUS_DECLINED,
                self::EST_STATUS_EXPIRED
            ];
            if ($estimate->estimate_status && !in_array($estimate->estimate_status, $valid_est)) {
                throw new \InvalidArgumentException("Invalid estimate_status: {$estimate->estimate_status}");
            }

            $valid_app = [
                self::APP_STATUS_NOT_REQUIRED,
                self::APP_STATUS_WAITING,
                self::APP_STATUS_APPROVED,
                self::APP_STATUS_CHANGES_REQUESTED,
                self::APP_STATUS_REJECTED
            ];
            if ($estimate->approval_status && !in_array($estimate->approval_status, $valid_app)) {
                throw new \InvalidArgumentException("Invalid approval_status: {$estimate->approval_status}");
            }

            $valid_clt = [
                self::CLT_STATUS_NOT_SENT,
                self::CLT_STATUS_SENT,
                self::CLT_STATUS_VIEWED,
                self::CLT_STATUS_ACCEPTED,
                self::CLT_STATUS_DECLINED,
                self::CLT_STATUS_EXPIRED
            ];
            if ($estimate->client_status && !in_array($estimate->client_status, $valid_clt)) {
                throw new \InvalidArgumentException("Invalid client_status: {$estimate->client_status}");
            }
        });

        // Handle cascade deletion of child versions when parent is deleted
        static::deleting(function ($estimate) {
            // If this is a root estimate (no parent_id), delete all its child versions
            if (!$estimate->parent_id) {
                // Get all child versions
                $childVersions = static::where('parent_id', $estimate->id)->get();

                foreach ($childVersions as $child) {
                    /** @var \App\Models\Estimate $child */
                    $child->delete();
                }
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
        'type',
        'subtotal',
        'tax_1',
        'tax_2',
        'total_tax',
        'discount_total',
        'discount_type',
        'discount_value',
        'grand_total',
        'total_cost',
        'gross_profit',
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
        'pdf_theme',
        'pdf_template_id',
        'view_count',
        'nudge_task_created',
        'created_by',
        'nurture_status',
        'engagement_score',
        'last_engagement_at',
        'last_nurtured_at',
        'last_viewed_at',
        'transportation_charges',
        'estimate_status',
        'client_status',
        'status', // Legacy support for mass-assignment
        'sent_at',
        'expires_at',
        'accepted_at',
        'declined_at',
    ];

    protected $appends = ['currency_symbol', 'has_discount', 'has_tax', 'has_transportation'];

    /**
     * Get the currency symbol from system settings or fallback to code-based mapping.
     */
    public function getCurrencySymbolAttribute()
    {
        return Setting::getCurrencySymbol();
    }

    /**
     * Standardized Visibility Rules
     */
    public function getHasDiscountAttribute()
    {
        return $this->discount_total > 0;
    }

    public function getHasTaxAttribute()
    {
        return $this->total_tax > 0;
    }

    public function getHasTransportationAttribute()
    {
        return $this->transportation_charges > 0;
    }

    /**
     * Virtual "status" accessor for backward-compatibility.
     *
     * Derives a single unified status string from the new structured fields
     * so that all legacy views using $estimate->status continue to work.
     *
     * Priority:
     *  1. client terminal states (accepted / declined) override everything
     *  2. estimate_status maps 'pending_approval' → 'waiting_approval'
     *  3. all other estimate_status values are returned as-is
     */
    public function getStatusAttribute(): string
    {
        // Internal rejection takes precedence for internal staff view
        if ($this->approval_status === self::APP_STATUS_REJECTED) {
            return 'rejected';
        }

        // Client has already responded — this is the most important state
        if ($this->client_status === self::CLT_STATUS_ACCEPTED) {
            return 'accepted';
        }
        if ($this->client_status === self::CLT_STATUS_DECLINED) {
            return 'declined';
        }

        // Map new internal statuses to old "display" values
        return match ($this->estimate_status) {
            self::EST_STATUS_PENDING_APPROVAL => 'waiting_approval',
            default => $this->estimate_status ?? 'draft',
        };
    }

    public function getHasClientNoteAttribute()
    {
        return !empty($this->client_note);
    }

    public function getHasAdminNoteAttribute()
    {
        return !empty($this->admin_note);
    }

    public function getHasTermsAttribute()
    {
        return !empty($this->terms);
    }

    /**
     * Get the final merged terms (global fallback, append, or replace)
     */
    public function getFinalTermsAttribute()
    {
        $globalTerms = trim(Setting::getCached('estimate_terms') ?? '');
        $localTerms = trim($this->terms ?? '');

        if (empty($localTerms)) {
            return $globalTerms;
        }

        // --- Smart Merge Logic ---

        // 1. Force Append: If it starts with '+', append local to global
        if (str_starts_with($localTerms, '+')) {
            $cleanedLocal = trim(substr($localTerms, 1));
            return ($globalTerms ? $globalTerms . "\n\n" : '') . $cleanedLocal;
        }

        // 2. Already Merged Check: If global terms are already part of local terms, just return local
        if (!empty($globalTerms) && str_contains($localTerms, $globalTerms)) {
            return $localTerms;
        }

        // 3. Default: Full Override (Replace)
        return $localTerms;
    }

    /**
     * Check if there are any final terms to display
     */
    public function getHasFinalTermsAttribute()
    {
        return !empty($this->final_terms);
    }

    protected static function booted()
    {
        static::deleting(function ($estimate) {
            // Prune associated activity logs to prevent orphanage
            ActivityLog::where('subject_type', Estimate::class)
                ->where('subject_id', $estimate->id)
                ->delete();
        });
    }

    /**
     * Get terms formatted for HTML rendering
     */
    public function getFinalTermsHtmlAttribute()
    {
        $terms = $this->final_terms;
        if (empty($terms)) {
            return '';
        }

        // If it looks like it already contains HTML, return it raw
        if ($terms !== strip_tags($terms)) {
            return $terms;
        }

        // Otherwise escape and convert newlines to <br>
        return nl2br(e($terms));
    }

    /**
     * Check if the estimate is expired.
     */
    public function isExpired(): bool
    {
        if ($this->estimate_status === self::EST_STATUS_EXPIRED) {
            return true;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Check if the estimate can be accepted by the client.
     */
    public function canBeAccepted(): bool
    {
        return $this->estimate_status === self::EST_STATUS_SENT
            && $this->client_status === self::CLT_STATUS_SENT
            && is_null($this->accepted_at)
            && ($this->expires_at ? now()->lt($this->expires_at) : true);
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'total_cost',
        'gross_profit',
        'admin_note',
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
        'expiry_date' => 'datetime',
        'signed_at' => 'datetime',
        'last_engagement_at' => 'datetime',
        'last_nurtured_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'email_opened_at' => 'datetime',
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
    ];



    public function sections()
    {
        return $this->hasMany(EstimateSection::class)->orderBy('order_index');
    }

    public function items()
    {
        return $this->hasMany(EstimateItem::class)->orderBy('order_index');
    }

    public function getPublicUrlAttribute()
    {
        // Expire link when estimate expires, or default to 30 days if null
        // We use the new 'expires_at' field for the signed URL
        $expiration = $this->expires_at ? $this->expires_at->endOfDay() : now()->addDays(30);

        // Pass parameters as an array for route binding
        return \Illuminate\Support\Facades\URL::temporarySignedRoute('portal.show', $expiration, ['estimate' => $this->id]);
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

    /**
     * Get all comments for the entire estimate family (all versions)
     */
    public function allFamilyComments()
    {
        $rootId = $this->parent_id ?? $this->id;
        return EstimateComment::whereIn('estimate_id', function ($query) use ($rootId) {
            $query->select('id')
                ->from('estimates')
                ->where(function ($q) use ($rootId) {
                    $q->where('id', $rootId)
                      ->orWhere('parent_id', $rootId);
                })
                ->whereNull('deleted_at');
        })->with(['user', 'commentable']);
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
     * Create approval records for a specific order step
     * @return \Illuminate\Support\Collection
     */
    public function createApprovalsForOrder($order)
    {
        $steps = $this->approvalChain->steps()->where('order', $order)->get();
        $createdApprovals = collect();

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
                $approval = EstimateApproval::create([
                    'estimate_id' => $this->id,
                    'user_id' => $userId,
                    'order' => $order,
                    'approval_chain_step_id' => $step->id,
                    'status' => 'pending',
                ]);
                $createdApprovals->push($approval);
            }
        }

        return $createdApprovals;
    }

    /**
     * Get the next pending approval steps (collection)
     */
    public function nextApprovalSteps()
    {
        if (!$this->approvalChain) {
            return collect();
        }

        // 1. Check if we have active pending approvals (no "next" until current is cleared)
        if ($this->approvals()->where('status', 'pending')->exists()) {
            return collect();
        }

        // 2. Find the highest order reached so far
        $lastOrder = $this->approvals()->max('order') ?? 0;

        // 3. Find the lowest order greater than $lastOrder
        $nextStep = $this->approvalChain->steps()
            ->where('order', '>', $lastOrder)
            ->orderBy('order')
            ->first();

        if ($nextStep) {
            return $this->approvalChain->steps()->where('order', $nextStep->order)->get();
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
    public function manualFollowers()
    {
        return $this->morphMany(Follower::class, 'followable');
    }

    public function getFollowersAttribute()
    {
        // 1. Creator
        $followers = collect();
        if ($this->creator) {
            $followers->push($this->creator);
        }

        // 2. Approvers (users in approval chain who have records)
        $approverIds = $this->approvals()->pluck('user_id')->unique();
        $approvers = \App\Models\User::whereIn('id', $approverIds)->get();

        // 3. Manual Followers
        $manualFollowerIds = $this->manualFollowers()->pluck('user_id');
        $manualFollowers = \App\Models\User::whereIn('id', $manualFollowerIds)->get();
        // Hydrate permissions into users for easy access if needed (conceptually) or just merge users.

        $followers = $followers->merge($approvers)->merge($manualFollowers);

        // Remove duplicates and self
        return $followers->unique('id');
    }

    public function userCanEdit($user)
    {
        if ($user->id == $this->created_by) {
            return true;
        }

        // Admin check is usually in Policy, but helper here is useful
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        $follower = $this->manualFollowers()->where('user_id', $user->id)->first();
        if ($follower && $follower->hasPermission('edit')) {
            return true;
        }

        return false;
    }

    public function validStatusTransitions()
    {
        switch ($this->estimate_status) {
            case self::EST_STATUS_DRAFT:
                return [self::EST_STATUS_PENDING_APPROVAL];
            case self::EST_STATUS_PENDING_APPROVAL:
                return [self::EST_STATUS_APPROVED];
            case self::EST_STATUS_APPROVED:
                return [self::EST_STATUS_SENT];
            case self::EST_STATUS_SENT:
                return [self::EST_STATUS_ACCEPTED, self::EST_STATUS_DECLINED, self::EST_STATUS_EXPIRED];
            case self::EST_STATUS_ACCEPTED:
            case self::EST_STATUS_DECLINED:
            case self::EST_STATUS_EXPIRED:
                return [];
            default:
                return [];
        }
    }

    public function canTransitionTo($newStatus, $user = null)
    {
        if ($newStatus === $this->estimate_status)
            return true;

        $user = $user ?? auth()->user();

        // Admin override
        if ($user && $user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Standard Lifecycle
        $valid = $this->validStatusTransitions();
        return in_array($newStatus, $valid);
    }
}
