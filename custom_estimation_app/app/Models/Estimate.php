<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Estimate extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';
    const STATUS_EXPIRED = 'expired';
    const STATUS_WAITING_APPROVAL = 'waiting_approval';

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
        'client_notes',
        'approval_chain_id',
        'approval_status',
        'coupon_code_id',
        'coupon_discount',
        'item_discounts_total',
        'room_discounts_total',
        'perfex_proposal_id',
        'pdf_theme',
        'view_count',
        'nudge_task_created',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function parent()
    {
        return $this->belongsTo(Estimate::class, 'parent_id');
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
    public function submitForApproval()
    {
        if (!$this->approval_chain_id) {
            throw new \Exception('No approval chain assigned to this estimate');
        }

        $this->update(['approval_status' => 'submitted']);

        // Create approval record for first step
        $firstStep = $this->approvalChain->steps()->orderBy('order')->first();
        if ($firstStep) {
            EstimateApproval::create([
                'estimate_id' => $this->id,
                'user_id' => $firstStep->user_id,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Get the next pending approval step
     */
    public function nextApprovalStep()
    {
        if (!$this->approvalChain) {
            return null;
        }

        $completedSteps = $this->approvals()->where('status', 'approved')->count();
        return $this->approvalChain->steps()->orderBy('order')->skip($completedSteps)->first();
    }

    /**
     * Check if all approvals are complete
     */
    public function isFullyApproved()
    {
        if (!$this->approvalChain) {
            return false;
        }

        $totalSteps = $this->approvalChain->steps()->count();
        $approvedSteps = $this->approvals()->where('status', 'approved')->count();

        return $totalSteps > 0 && $totalSteps === $approvedSteps;
    }

    public function analytics()
    {
        return $this->hasMany(EstimateAnalytic::class);
    }

    public function pdfTemplate()
    {
        return $this->belongsTo(PdfTemplate::class);
    }
}
