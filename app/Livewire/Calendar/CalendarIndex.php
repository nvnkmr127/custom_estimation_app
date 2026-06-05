<?php

namespace App\Livewire\Calendar;

use Livewire\Component;
use App\Models\Reminder;
use App\Models\Estimate;
use App\Models\Task;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CalendarIndex extends Component
{
    public $year;
    public $month;

    // Reminder Creation Properties
    public $showReminderModal = false;
    public $newReminderDate;
    public $newReminderTime = '09:00';
    public $newReminderTitle = '';
    public $newReminderDescription = '';
    public $newReminderType = 'in_app';
    public $newReminderLinkType = 'general';
    public $newReminderLinkId = '';

    // Reminder Detail Inspection Properties
    public $showDetailModal = false;
    public $selectedEventDetails = [];

    public function mount()
    {
        $this->year = Carbon::now()->year;
        $this->month = Carbon::now()->month;
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function openCreateReminder($dateString)
    {
        $this->newReminderDate = $dateString;
        $this->newReminderTitle = '';
        $this->newReminderDescription = '';
        $this->newReminderType = 'in_app';
        $this->newReminderLinkType = 'general';
        $this->newReminderLinkId = '';
        $this->showReminderModal = true;
    }

    public function saveReminder()
    {
        $this->validate([
            'newReminderTitle' => 'required|string|max:255',
            'newReminderDescription' => 'nullable|string',
            'newReminderDate' => 'required|date',
            'newReminderTime' => 'required|string',
            'newReminderType' => 'required|in:in_app,email,both',
        ]);

        $remindAt = Carbon::parse($this->newReminderDate . ' ' . $this->newReminderTime);
        if ($remindAt->isPast()) {
            $this->addError('newReminderDate', 'Reminder time must be in the future.');
            return;
        }

        $remindableType = \App\Models\User::class;
        $remindableId = Auth::id();

        if ($this->newReminderLinkType === 'estimate' && $this->newReminderLinkId) {
            $remindableType = Estimate::class;
            $remindableId = $this->newReminderLinkId;
        } elseif ($this->newReminderLinkType === 'client' && $this->newReminderLinkId) {
            $remindableType = Client::class;
            $remindableId = $this->newReminderLinkId;
        } elseif ($this->newReminderLinkType === 'task' && $this->newReminderLinkId) {
            $remindableType = Task::class;
            $remindableId = $this->newReminderLinkId;
        }

        Reminder::create([
            'user_id' => Auth::id(),
            'title' => $this->newReminderTitle,
            'description' => $this->newReminderDescription,
            'remindable_type' => $remindableType,
            'remindable_id' => $remindableId,
            'remind_at' => $remindAt,
            'type' => $this->newReminderType,
            'is_sent' => false,
        ]);

        $this->showReminderModal = false;
        session()->flash('success', 'Reminder created successfully.');
    }

    public function showEventDetails($type, $id)
    {
        $details = [];

        if ($type === 'reminder') {
            $reminder = Reminder::with('remindable')->find($id);
            if ($reminder && $reminder->user_id === Auth::id()) {
                $details = [
                    'type' => 'reminder',
                    'title' => $reminder->title,
                    'description' => $reminder->description ?? 'No description provided.',
                    'date' => $reminder->remind_at->format('M d, Y H:i'),
                    'delivery' => ucfirst($reminder->type),
                    'status' => $reminder->is_sent ? 'Sent' : 'Pending',
                    'link_label' => $reminder->remindable_type !== \App\Models\User::class ? class_basename($reminder->remindable_type) : null,
                    'link_url' => $this->resolveEntityUrl($reminder->remindable_type, $reminder->remindable_id),
                ];
            }
        } elseif ($type === 'estimate') {
            $estimate = Estimate::with('client')->find($id);
            if ($estimate) {
                $details = [
                    'type' => 'estimate',
                    'title' => 'Estimate #' . $estimate->estimate_number . ' Expiration',
                    'description' => 'Client: ' . ($estimate->client?->name ?? 'N/A') . "\nStatus: " . ucfirst($estimate->estimate_status) . "\nTotal: ₹" . number_format($estimate->grand_total, 2),
                    'date' => $estimate->expiry_date ? $estimate->expiry_date->format('M d, Y') : 'N/A',
                    'status' => ucfirst($estimate->estimate_status),
                    'link_label' => 'View Estimate Sheet',
                    'link_url' => route('estimates.show', $estimate->id),
                ];
            }
        } elseif ($type === 'task') {
            $task = Task::find($id);
            if ($task) {
                $details = [
                    'type' => 'task',
                    'title' => 'Task: ' . $task->title,
                    'description' => ($task->description ?? 'No description.') . "\nPriority: " . ucfirst($task->priority) . "\nStatus: " . ucfirst(str_replace('_', ' ', $task->status)),
                    'date' => $task->due_date ? $task->due_date->format('M d, Y') : 'N/A',
                    'status' => ucfirst(str_replace('_', ' ', $task->status)),
                    'link_label' => 'View Task Details',
                    'link_url' => route('tasks.show', $task->id),
                ];
            }
        }

        if (!empty($details)) {
            $this->selectedEventDetails = $details;
            $this->showDetailModal = true;
        }
    }

    private function resolveEntityUrl($class, $id)
    {
        if ($class === Estimate::class) {
            return route('estimates.show', $id);
        }
        if ($class === Client::class) {
            return route('clients.show', $id);
        }
        if ($class === Task::class) {
            return route('tasks.show', $id);
        }
        return null;
    }

    public function render()
    {
        $user = Auth::user();
        $isAdmin = $user->isAdmin();

        // 1. Calculate dates for monthly grid view
        $startOfMonth = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Start day of week (0 = Sunday, 1 = Monday, etc.)
        $startDayOfWeek = $startOfMonth->dayOfWeek;
        
        $gridDays = [];

        // Add padding days from the previous month
        $prevMonthDate = $startOfMonth->copy()->subDays($startDayOfWeek);
        for ($i = 0; $i < $startDayOfWeek; $i++) {
            $dateStr = $prevMonthDate->format('Y-m-d');
            $gridDays[] = [
                'date' => $dateStr,
                'day' => $prevMonthDate->day,
                'is_current_month' => false,
                'events' => [],
            ];
            $prevMonthDate->addDay();
        }

        // Add days of the current month
        $currentDate = $startOfMonth->copy();
        while ($currentDate->month === $this->month) {
            $dateStr = $currentDate->format('Y-m-d');
            $gridDays[] = [
                'date' => $dateStr,
                'day' => $currentDate->day,
                'is_current_month' => true,
                'events' => [],
            ];
            $currentDate->addDay();
        }

        // Add padding days from the next month to fit a 7-column grid rows
        $totalDays = count($gridDays);
        $remaining = (7 - ($totalDays % 7)) % 7;
        for ($i = 0; $i < $remaining; $i++) {
            $dateStr = $currentDate->format('Y-m-d');
            $gridDays[] = [
                'date' => $dateStr,
                'day' => $currentDate->day,
                'is_current_month' => false,
                'events' => [],
            ];
            $currentDate->addDay();
        }

        // 2. Fetch events falling into this monthly grid time window
        $gridStart = Carbon::parse($gridDays[0]['date'])->startOfDay();
        $gridEnd = Carbon::parse(end($gridDays)['date'])->endOfDay();

        // Reminders
        $reminders = Reminder::forUser(Auth::id())
            ->whereBetween('remind_at', [$gridStart, $gridEnd])
            ->get();

        // Estimates Expiry Dates
        $estimatesQuery = Estimate::query()
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$gridStart, $gridEnd]);

        if (!$isAdmin) {
            $estimatesQuery->where('created_by', Auth::id());
        }
        $estimates = $estimatesQuery->get();

        // Tasks Due Dates
        $tasksQuery = Task::query()
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$gridStart, $gridEnd]);

        if (!$isAdmin) {
            $tasksQuery->where(function ($q) {
                $q->where('assigned_to', Auth::id())
                  ->orWhere('created_by', Auth::id());
            });
        }
        $tasks = $tasksQuery->get();

        // 3. Map events to the calendar day cells
        foreach ($gridDays as &$day) {
            $dayDate = Carbon::parse($day['date']);

            // Filter reminders
            foreach ($reminders as $rem) {
                if ($rem->remind_at->toDateString() === $day['date']) {
                    $day['events'][] = [
                        'type' => 'reminder',
                        'id' => $rem->id,
                        'title' => $rem->title,
                        'time' => $rem->remind_at->format('H:i'),
                        'color' => 'bg-amber-100 text-amber-800 border-amber-200 hover:bg-amber-200',
                    ];
                }
            }

            // Filter estimates
            foreach ($estimates as $est) {
                if ($est->expiry_date->toDateString() === $day['date']) {
                    $day['events'][] = [
                        'type' => 'estimate',
                        'id' => $est->id,
                        'title' => 'Expiry: EST #' . $est->estimate_number,
                        'time' => '17:00',
                        'color' => 'bg-rose-100 text-rose-800 border-rose-200 hover:bg-rose-200',
                    ];
                }
            }

            // Filter tasks
            foreach ($tasks as $tsk) {
                if ($tsk->due_date->toDateString() === $day['date']) {
                    $day['events'][] = [
                        'type' => 'task',
                        'id' => $tsk->id,
                        'title' => 'Task: ' . $tsk->title,
                        'time' => '09:00',
                        'color' => 'bg-emerald-100 text-emerald-800 border-emerald-200 hover:bg-emerald-200',
                    ];
                }
            }

            // Sort day events by time
            usort($day['events'], function ($a, $b) {
                return strcmp($a['time'], $b['time']);
            });
        }

        // Load dropdown fields for reminder modal
        $activeEstimates = Estimate::query()
            ->select('id', 'estimate_number')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();

        $activeClients = Client::query()
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->limit(50)
            ->get();

        $activeTasks = Task::query()
            ->select('id', 'title')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();

        return view('livewire.calendar.calendar-index', [
            'gridDays' => $gridDays,
            'estimates' => $activeEstimates,
            'clients' => $activeClients,
            'tasks' => $activeTasks,
            'monthName' => Carbon::create($this->year, $this->month, 1)->format('F Y'),
        ])->layout('layouts.app');
    }
}
