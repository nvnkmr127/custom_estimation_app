<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Estimate;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Task::with(['assignedTo', 'estimate', 'client']);

        if (!auth()->user()->isAdmin()) {
            $query->where(function ($q) {
                $q->where('assigned_to', auth()->id())
                    ->orWhere('created_by', auth()->id());
            });
        }

        // Filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('assigned_to') && $request->assigned_to) {
            if ($request->assigned_to === 'me') {
                $query->myTasks();
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        $tasks = $query->latest()->paginate(15);

        return view('tasks.index', compact('tasks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::all();
        $estimates = Estimate::latest()->take(50)->get();
        $clients = Client::orderBy('name')->get();

        return view('tasks.create', compact('users', 'estimates', 'clients'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'estimate_id' => 'nullable|exists:estimates,id',
            'client_id' => 'nullable|exists:clients,id',
            'due_date' => 'nullable|date',
        ]);

        $validated['created_by'] = auth()->id();

        $task = Task::create($validated);

        ActivityLog::log('created', $task, "Task '{$task->title}' was created");

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        $task->load(['assignedTo', 'createdBy', 'estimate', 'client']);

        return view('tasks.show', compact('task'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task)
    {
        $users = User::all();
        $estimates = Estimate::latest()->take(50)->get();
        $clients = Client::orderBy('name')->get();

        return view('tasks.edit', compact('task', 'users', 'estimates', 'clients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'estimate_id' => 'nullable|exists:estimates,id',
            'client_id' => 'nullable|exists:clients,id',
            'due_date' => 'nullable|date',
        ]);

        $task->update($validated);

        ActivityLog::log('updated', $task, "Task '{$task->title}' was updated");

        return redirect()->route('tasks.show', $task)->with('success', 'Task updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        ActivityLog::log('deleted', $task, "Task '{$task->title}' was deleted");
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }

    /**
     * Mark task as complete
     */
    public function complete(Task $task)
    {
        $task->complete();
        ActivityLog::log('completed', $task, "Task '{$task->title}' was marked as completed");

        return back()->with('success', 'Task marked as completed.');
    }
}
