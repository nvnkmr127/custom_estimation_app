<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $reminders = Reminder::forUser(auth()->id())
            ->with('remindable')
            ->orderBy('remind_at', 'asc')
            ->paginate(20);

        // Pre-fill remindable from request if valid
        $remindableType = $request->query('remindable_type');
        $remindableId = $request->query('remindable_id');
        $linkedEntityInfo = null;

        if ($remindableType && $remindableId) {
            if (class_exists($remindableType) && is_subclass_of($remindableType, \Illuminate\Database\Eloquent\Model::class)) {
                $entity = $remindableType::find($remindableId);
                if ($entity) {
                    if ($remindableType === \App\Models\Estimate::class) {
                        $linkedEntityInfo = 'Estimate #' . $entity->estimate_number;
                    } elseif ($remindableType === \App\Models\Client::class) {
                        $linkedEntityInfo = 'Client: ' . $entity->name;
                    } elseif ($remindableType === \App\Models\Task::class) {
                        $linkedEntityInfo = 'Task: ' . $entity->title;
                    } else {
                        $linkedEntityInfo = class_basename($remindableType) . ' (ID: ' . $entity->id . ')';
                    }
                } else {
                    $remindableType = null;
                    $remindableId = null;
                }
            } else {
                $remindableType = null;
                $remindableId = null;
            }
        }

        // Fetch entities for selection dropdowns
        $estimates = \App\Models\Estimate::query()
            ->select('id', 'estimate_number')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();

        $clients = \App\Models\Client::query()
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->limit(50)
            ->get();

        $tasks = \App\Models\Task::query()
            ->select('id', 'title')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();

        return view('reminders.index', compact(
            'reminders', 
            'remindableType', 
            'remindableId', 
            'linkedEntityInfo', 
            'estimates', 
            'clients', 
            'tasks'
        ));
    }

    /**
     * Store a newly created reminder.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'remindable_type' => 'required|string',
            'remindable_id' => 'required|integer',
            'remind_at' => 'required|date|after:now',
            'type' => 'required|in:email,in_app,both',
        ]);

        $validated['user_id'] = auth()->id();

        Reminder::create($validated);

        return back()->with('success', 'Reminder set successfully.');
    }

    /**
     * Remove the specified reminder.
     */
    public function destroy(Reminder $reminder)
    {
        if ($reminder->user_id !== auth()->id()) {
            abort(403);
        }

        $reminder->delete();

        return back()->with('success', 'Reminder deleted.');
    }

    /**
     * Mark reminder as read
     */
    public function markAsRead(Reminder $reminder)
    {
        if ($reminder->user_id !== auth()->id()) {
            abort(403);
        }

        $reminder->markAsSent();

        return back()->with('success', 'Reminder dismissed.');
    }
}
