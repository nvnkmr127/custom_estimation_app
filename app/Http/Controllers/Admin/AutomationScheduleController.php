<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationSchedule;
use App\Services\Automation\ScheduleService;
use Illuminate\Http\Request;

class AutomationScheduleController extends Controller
{
    protected ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * List schedules for an automation.
     */
    public function index(Automation $automation)
    {
        $schedules = $automation->schedules()
            ->orderBy('next_run_at')
            ->get()
            ->map(function ($schedule) {
                $schedule->human_readable = $this->scheduleService->getHumanReadableSchedule($schedule->cron_expression);
                return $schedule;
            });

        return response()->json([
            'schedules' => $schedules,
            'presets' => $this->scheduleService->getPresets(),
        ]);
    }

    /**
     * Create a new schedule.
     */
    public function store(Request $request, Automation $automation)
    {
        $request->validate([
            'cron_expression' => 'required|string',
            'timezone' => 'required|timezone',
            'name' => 'nullable|string|max:255',
        ]);

        if (!$this->scheduleService->validateCronExpression($request->cron_expression)) {
            return response()->json(['message' => 'Invalid cron expression'], 422);
        }

        $nextRun = $this->scheduleService->calculateNextRun($request->cron_expression, $request->timezone);

        $schedule = $automation->schedules()->create([
            'name' => $request->name,
            'cron_expression' => $request->cron_expression,
            'timezone' => $request->timezone,
            'next_run_at' => $nextRun,
            'is_active' => true,
        ]);

        $schedule->human_readable = $this->scheduleService->getHumanReadableSchedule($schedule->cron_expression);

        return response()->json([
            'message' => 'Schedule created successfully',
            'schedule' => $schedule,
        ]);
    }

    /**
     * Update a schedule.
     */
    public function update(Request $request, AutomationSchedule $schedule)
    {
        $request->validate([
            'cron_expression' => 'required|string',
            'timezone' => 'required|timezone',
            'name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if (!$this->scheduleService->validateCronExpression($request->cron_expression)) {
            return response()->json(['message' => 'Invalid cron expression'], 422);
        }

        // Recalculate next run if cron or timezone changed
        $nextRun = $schedule->next_run_at;
        if (
            $request->cron_expression !== $schedule->cron_expression ||
            $request->timezone !== $schedule->timezone
        ) {
            $nextRun = $this->scheduleService->calculateNextRun($request->cron_expression, $request->timezone);
        }

        $schedule->update([
            'name' => $request->name,
            'cron_expression' => $request->cron_expression,
            'timezone' => $request->timezone,
            'next_run_at' => $nextRun,
            'is_active' => $request->is_active ?? $schedule->is_active,
        ]);

        $schedule->human_readable = $this->scheduleService->getHumanReadableSchedule($schedule->cron_expression);

        return response()->json([
            'message' => 'Schedule updated successfully',
            'schedule' => $schedule,
        ]);
    }

    /**
     * Delete a schedule.
     */
    public function destroy(AutomationSchedule $schedule)
    {
        $schedule->delete();

        return response()->json([
            'message' => 'Schedule deleted successfully',
        ]);
    }

    /**
     * Toggle a schedule.
     */
    public function toggle(AutomationSchedule $schedule)
    {
        $schedule->update(['is_active' => !$schedule->is_active]);

        return response()->json([
            'message' => 'Schedule updated successfully',
            'is_active' => $schedule->is_active,
        ]);
    }
}
