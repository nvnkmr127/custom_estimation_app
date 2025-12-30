<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the log.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        // Filters
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        if ($request->has('subject_type') && $request->subject_type) {
            $query->where('subject_type', $request->subject_type);
        }

        $activities = $query->latest()->paginate(50);
        $users = User::orderBy('name')->get();

        return view('activities.index', compact('activities', 'users'));
    }

    /**
     * Display the specified log entry.
     */
    public function show(ActivityLog $activity)
    {
        $activity->load(['user', 'subject']);

        return view('activities.show', compact('activity'));
    }
}
