<?php

namespace App\Livewire\Estimates;

use App\Models\Estimate;
use App\Models\ActivityLog;
use App\Models\DeclineReason;
use App\Models\EstimateApproval;
use App\Models\EstimateItem;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ShowEstimate extends Component
{
    public Estimate $estimate;
    public $viewCount;
    public $lastActivity;
    public $allVersions;
    public $latestVersion;
    public $checklists;
    public $declineReasons;
    public $userApproval;
    public $activityLogs;

    protected $listeners = [
        'estimateUpdated' => 'refreshEstimate',
        'statsUpdated' => 'refreshStats'
    ];

    public function mount($estimate)
    {
        $estimateId = $estimate instanceof Estimate ? $estimate->id : $estimate;

        // Load estimate with all relationships
        $this->estimate = Estimate::with([
            'client',
            'items.product.images',
            'items.unitType',
            'items.comments.user',
            'sections.items.product.images',
            'sections.items.unitType',
            'sections.items.comments.user',
            'approvals.user',
            'pdfTemplate',
            'approvalChain.approvers',
            'checklistItems.checklist'
        ])->findOrFail($estimateId);

        // Initialize stats
        $this->viewCount = $this->estimate->view_count;
        $this->lastActivity = $this->estimate->updated_at;

        // Load all versions
        $this->allVersions = Estimate::where('parent_id', $this->estimate->parent_id ?? $this->estimate->id)
            ->orWhere('id', $this->estimate->parent_id ?? $this->estimate->id)
            ->orderBy('version', 'desc')
            ->get();

        $this->latestVersion = $this->allVersions->first();

        // Load checklists if in approval workflow
        if ($this->estimate->status === 'waiting_approval' && $this->estimate->approvalChain) {
            // Load all checklists - they are global
            $this->checklists = \App\Models\ApprovalChecklist::all();
        } else {
            $this->checklists = collect();
        }

        // Load decline reasons
        $this->declineReasons = DeclineReason::all();

        // Check if current user is an approver
        $this->userApproval = EstimateApproval::where('estimate_id', $this->estimate->id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->first();

        // Load activity logs
        $this->activityLogs = ActivityLog::where('subject_type', Estimate::class)
            ->where('subject_id', $this->estimate->id)
            ->with('user')
            ->latest()
            ->get();
    }

    public function refreshStats()
    {
        $this->estimate->refresh();
        $this->viewCount = $this->estimate->view_count;
        $this->lastActivity = $this->estimate->updated_at;
    }

    public function toggleChecklist($checklistId, $completed)
    {
        try {
            app(\App\Http\Controllers\ApprovalController::class)
                ->toggleChecklistItem(request()->merge([
                    'checklist_id' => $checklistId,
                    'completed' => $completed
                ]), $this->estimate);

            $this->dispatch('statsUpdated');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update checklist: ' . $e->getMessage());
        }
    }

    public function refreshEstimate()
    {
        $this->estimate->refresh();
        $this->mount($this->estimate->id);
    }

    public function approve($comments = null)
    {
        try {
            DB::beginTransaction();

            // Call the ApprovalController method for approval logic
            app(\App\Http\Controllers\ApprovalController::class)
                ->approve(request()->merge(['comments' => $comments]), $this->estimate);

            DB::commit();

            $this->refreshEstimate();
            $this->dispatch('estimateUpdated');

            session()->flash('success', 'Estimate approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to approve estimate: ' . $e->getMessage());
        }
    }

    public function reject($reasonId, $comments)
    {
        try {
            DB::beginTransaction();

            // Call the ApprovalController method for rejection logic
            app(\App\Http\Controllers\ApprovalController::class)
                ->reject(request()->merge([
                    'reason_id' => $reasonId,
                    'comments' => $comments
                ]), $this->estimate);

            DB::commit();

            $this->refreshEstimate();
            $this->dispatch('estimateUpdated');

            session()->flash('success', 'Estimate rejected.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to reject estimate: ' . $e->getMessage());
        }
    }

    public function requestChanges($comments)
    {
        try {
            DB::beginTransaction();

            // Call the ApprovalController method for request changes logic
            app(\App\Http\Controllers\ApprovalController::class)
                ->requestChanges(request()->merge(['comments' => $comments]), $this->estimate);

            DB::commit();

            $this->refreshEstimate();
            $this->dispatch('estimateUpdated');

            session()->flash('success', 'Changes requested successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to request changes: ' . $e->getMessage());
        }
    }

    public function createVersion()
    {
        try {
            DB::beginTransaction();

            // Call the controller method for version creation
            $response = app(\App\Http\Controllers\EstimateController::class)
                ->createVersion($this->estimate);

            DB::commit();

            // Redirect to the new version
            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                return $response;
            }

            $this->dispatch('estimateUpdated');
            session()->flash('success', 'New version created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to create version: ' . $e->getMessage());
        }
    }

    public function markAs($status)
    {
        try {
            DB::beginTransaction();

            // Call the controller method for status change
            app(\App\Http\Controllers\EstimateController::class)
                ->markAs(request(), $this->estimate, $status);

            DB::commit();

            $this->refreshEstimate();
            $this->dispatch('estimateUpdated');

            session()->flash('success', 'Status updated to ' . ucfirst($status));
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to update status: ' . $e->getMessage());
        }
    }
    public function submitForApproval()
    {
        try {
            $response = app()->call([app(\App\Http\Controllers\ApprovalController::class), 'submit'], ['estimate' => $this->estimate]);
            if ($response instanceof RedirectResponse)
                return $response;
            $this->refreshEstimate();
            session()->flash('success', 'Estimate submitted for approval.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to submit for approval: ' . $e->getMessage());
        }
    }

    public function sendToClient()
    {
        try {
            $response = app()->call([app(\App\Http\Controllers\EstimateController::class), 'sendToClient'], ['estimate' => $this->estimate]);
            if ($response instanceof RedirectResponse)
                return $response;
            $this->refreshEstimate();
            session()->flash('success', 'Estimate sent to client.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to send estimate: ' . $e->getMessage());
        }
    }

    public function discard()
    {
        try {
            $response = app()->call([app(\App\Http\Controllers\EstimateController::class), 'destroy'], ['estimate' => $this->estimate]);
            if ($response instanceof RedirectResponse)
                return $response;
            return redirect()->route('estimates.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to discard estimate: ' . $e->getMessage());
        }
    }

    public function duplicate()
    {
        try {
            $response = app()->call([app(\App\Http\Controllers\EstimateController::class), 'copy'], ['estimate' => $this->estimate]);
            if ($response instanceof RedirectResponse)
                return $response;
            $this->refreshEstimate();
            session()->flash('success', 'Estimate duplicated.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to duplicate: ' . $e->getMessage());
        }
    }

    public function perfexSync()
    {
        try {
            $response = app()->call([app(\App\Http\Controllers\PerfexController::class), 'sync'], ['estimate' => $this->estimate]);
            if ($response instanceof RedirectResponse)
                return $response;
            $this->refreshEstimate();
            session()->flash('success', 'Synced with Perfex.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to sync with Perfex: ' . $e->getMessage());
        }
    }

    public function revertToDraft()
    {
        try {
            $response = app()->call([app(\App\Http\Controllers\EstimateController::class), 'revertToDraft'], ['estimate' => $this->estimate]);
            if ($response instanceof RedirectResponse)
                return $response;
            $this->refreshEstimate();
            session()->flash('success', 'Estimate reverted to draft.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to revert to draft: ' . $e->getMessage());
        }
    }

    public function duplicateItem($itemId)
    {
        try {
            $item = EstimateItem::findOrFail($itemId);
            app()->call([app(\App\Http\Controllers\EstimateController::class), 'duplicateItem'], ['item' => $item]);
            $this->refreshEstimate();
            session()->flash('success', 'Item duplicated.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to duplicate item: ' . $e->getMessage());
        }
    }

    public function addComment($comment)
    {
        try {
            app()->call([app(\App\Http\Controllers\EstimateController::class), 'storeComment'], [
                'request' => request()->merge(['comment' => $comment]),
                'estimate' => $this->estimate
            ]);
            $this->refreshEstimate();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add comment: ' . $e->getMessage());
        }
    }

    public function addItemComment($itemId, $comment)
    {
        try {
            app()->call([app(\App\Http\Controllers\CommentController::class), 'store'], [
                'request' => request()->merge(['comment' => $comment, 'estimate_item_id' => $itemId]),
                'estimate' => $this->estimate
            ]);
            $this->refreshEstimate();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add item comment: ' . $e->getMessage());
        }
    }

    public function approveVersion()
    {
        try {
            $response = app()->call([app(\App\Http\Controllers\EstimateController::class), 'approveVersion'], ['estimate' => $this->estimate]);
            if ($response instanceof RedirectResponse)
                return $response;
            $this->refreshEstimate();
            session()->flash('success', 'Version approved.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to approve version: ' . $e->getMessage());
        }
    }

    public function addFollower($userId)
    {
        try {
            app()->call([app(\App\Http\Controllers\EstimateController::class), 'addFollower'], [
                'request' => request()->merge(['user_id' => $userId]),
                'estimate' => $this->estimate
            ]);
            $this->refreshEstimate();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add follower: ' . $e->getMessage());
        }
    }

    public function removeFollower($userId)
    {
        try {
            $user = \App\Models\User::findOrFail($userId);
            app()->call([app(\App\Http\Controllers\EstimateController::class), 'removeFollower'], [
                'estimate' => $this->estimate,
                'user' => $user
            ]);
            $this->refreshEstimate();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to remove follower: ' . $e->getMessage());
        }
    }

    public function toggleCommentStatus($commentId, $currentStatus)
    {
        try {
            $newStatus = $currentStatus === 'pending' ? 'clarified' : 'pending';
            // Use DB to update directly as it's a simple status change
            DB::table('comments')->where('id', $commentId)->update(['status' => $newStatus]);
            $this->refreshEstimate();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update comment status: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.estimates.show-estimate');
    }
}
