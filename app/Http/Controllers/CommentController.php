<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\EstimateComment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    protected $dispatcher;

    public function __construct(\App\Core\Events\EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    /**
     * Store a new comment
     */
    public function store(Request $request, Estimate $estimate)
    {
        $this->authorize('update', $estimate);

        if ($request->wantsJson()) {
            if ($request->has('content') && !$request->has('comment')) {
                $request->merge(['comment' => $request->input('content')]);
            }
            if (!$request->has('commentable_type')) {
                $request->merge(['commentable_type' => Estimate::class]);
            }
            if (!$request->has('commentable_id')) {
                $request->merge(['commentable_id' => $estimate->id]);
            }
        }

        $validated = $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'nullable|integer',
            'comment' => 'required|string|max:5000',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'parent_id' => 'nullable|exists:estimate_comments,id',
        ]);

        // Determine comment type
        $type = auth()->check() ? 'internal' : 'client';

        // If replying to a comment, inherit the context (Item vs Estimate)
        if (!empty($validated['parent_id'])) {
            $parent = EstimateComment::where('estimate_id', $estimate->id)
                ->find($validated['parent_id']);
                
            if (!$parent) {
                return response()->json(['success' => false, 'message' => 'Invalid parent comment context.'], 400);
            }
            
            $validated['commentable_type'] = $parent->commentable_type;
            $validated['commentable_id'] = $parent->commentable_id;
        }

        $comment = $estimate->comments()->create([
            'commentable_type' => $validated['commentable_type'],
            'commentable_id' => $validated['commentable_id'] ?? null,
            'user_id' => auth()->id(),
            'client_name' => $validated['client_name'] ?? null,
            'client_email' => $validated['client_email'] ?? null,
            'comment' => $validated['comment'],
            'type' => $type,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        // Dispatch Event
        $this->dispatcher->dispatch(new \App\Core\Events\Comments\CommentAdded(
            $comment->id,
            $estimate->id,
            $comment->user_id, // This is null for client comments?
            substr($comment->comment, 0, 100)
        ));

        if ($type === 'client') {
            $this->notifyTeam($estimate, $comment);
        } else {
            $estimate->followers
                ->reject(fn ($user) => $user->id === auth()->id())
                ->each(fn ($user) => $user->notify(new \App\Notifications\EstimateCommentNotification($comment, $estimate)));
        }

        \Log::info("CommentController: Created comment ID {$comment->id} Type: {$type} On: {$comment->commentable_type} #{$comment->commentable_id}");

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'comment' => [
                    'id' => $comment->id,
                    'content' => $comment->comment,
                    'created_at' => $comment->created_at ? $comment->created_at->toISOString() : null,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'comment' => $comment->load('user', 'replies'),
            'message' => 'Comment added successfully',
        ]);
    }

    /**
     * Get all comments for an estimate
     */
    public function index(Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        $query = $estimate->comments()
            ->with(['user', 'commentable', 'replies.user', 'replies' => function($q) {
                if (!auth()->check()) {
                   $q->where('type', 'client');
                }
            }])
            ->whereNull('parent_id');

        // Privacy: Filter out internal notes for clients
        if (!auth()->check()) {
            $query->where('type', 'client');
        }

        $comments = $query->latest()->get();

        if (request()->wantsJson()) {
            return response()->json($comments->map(function ($c) {
                return [
                    'id' => $c->id,
                    'estimate_id' => $c->estimate_id,
                    'user_id' => $c->user_id,
                    'content' => $c->comment,
                    'created_at' => $c->created_at ? $c->created_at->toISOString() : null,
                    'user' => $c->user ? [
                        'name' => $c->user->name
                    ] : null,
                ];
            }));
        }

        return response()->json($comments);
    }

    /**
     * Mark comment as read
     */
    public function markAsRead(EstimateComment $comment)
    {
        // Security: Prevent IDOR across estimates
        $this->authorize('update', $comment->estimate);
        
        $comment->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Comment marked as read',
        ]);
    }

    /**
     * Mark all comments as read for an estimate
     */
    public function markAllAsRead(Estimate $estimate)
    {
        $this->authorize('update', $estimate);

        $estimate->comments()
            ->unread()
            ->clientComments()
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All comments marked as read',
        ]);
    }

    /**
     * Delete a comment
     */
    public function destroy(EstimateComment $comment)
    {
        // Security: Prevent IDOR across estimates
        $this->authorize('update', $comment->estimate);

        // Only allow deleting own comments or if admin
        if (auth()->id() !== $comment->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
        ]);
    }

    /**
     * Update comment status
     */
    public function updateStatus(Request $request, EstimateComment $comment)
    {
        // Security: Prevent IDOR across estimates
        $this->authorize('update', $comment->estimate);

        $validated = $request->validate([
            'status' => 'required|in:pending,clarified',
        ]);

        $comment->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Comment status updated',
            'status' => $comment->status,
        ]);
    }

    /**
     * Notify team of new client comment
     */
    private function notifyTeam($estimate, $comment)
    {
        $recipients = $estimate->followers
            ->merge(\App\Models\User::whereIn('role', ['super_admin', 'estimator_admin', 'estimator_manager'])->get())
            ->unique('id');

        $recipients->each(fn ($user) => $user->notify(new \App\Notifications\EstimateCommentNotification($comment, $estimate)));
    }
}
