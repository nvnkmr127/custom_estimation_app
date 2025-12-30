<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    /**
     * Display a listing of reminders.
     */
    public function index()
    {
        $reminders = Reminder::forUser(auth()->id())
            ->with('remindable')
            ->orderBy('remind_at', 'asc')
            ->paginate(20);

        return view('reminders.index', compact('reminders'));
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
