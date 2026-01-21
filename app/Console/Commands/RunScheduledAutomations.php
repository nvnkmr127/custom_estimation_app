<?php

namespace App\Console\Commands;

use App\Services\Automation\ScheduleService;
use App\Services\Automation\AutomationService;
use App\Core\Events\ScheduledEvent;
use Illuminate\Console\Command;

class RunScheduledAutomations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:run-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute automations that are due based on their schedules';

    protected ScheduleService $scheduleService;
    protected AutomationService $automationService;

    public function __construct(ScheduleService $scheduleService, AutomationService $automationService)
    {
        parent::__construct();
        $this->scheduleService = $scheduleService;
        $this->automationService = $automationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for scheduled automations...');

        $dueSchedules = $this->scheduleService->getDueSchedules();

        if ($dueSchedules->isEmpty()) {
            $this->info('No scheduled automations are due.');
            return 0;
        }

        $this->info("Found {$dueSchedules->count()} scheduled automation(s) to run.");

        foreach ($dueSchedules as $schedule) {
            try {
                $automation = $schedule->automation;

                if (!$automation || !$automation->is_active) {
                    $this->warn("Skipping schedule #{$schedule->id}: Automation is inactive or deleted.");
                    continue;
                }

                $this->info("Executing: {$automation->name} (Schedule: {$schedule->name})");

                // Create a synthetic DomainEvent to trigger the automation
                // Get the first trigger's event name
                $trigger = $automation->triggers->first();
                if (!$trigger) {
                    $this->warn("Skipping schedule #{$schedule->id}: No trigger defined for automation.");
                    continue;
                }

                // Create a domain event for the scheduled trigger
                $event = new ScheduledEvent(
                    $trigger->event_name,
                    [
                        'scheduled' => true,
                        'schedule_id' => $schedule->id,
                        'automation_id' => $automation->id,
                        'triggered_at' => now()->toIso8601String(),
                    ]
                );

                // Trigger the automation
                $this->automationService->evaluate($event);

                // Update last run time
                $schedule->update(['last_run_at' => now()]);

                // Calculate and update next run time
                $this->scheduleService->updateNextRunTime($schedule);

                $this->info("✓ Completed: {$automation->name}");
                $this->info("  Next run: {$schedule->fresh()->next_run_at->format('Y-m-d H:i:s')}");

            } catch (\Exception $e) {
                $this->error("✗ Failed to execute schedule #{$schedule->id}: {$e->getMessage()}");
                \Log::error('Scheduled automation execution failed', [
                    'schedule_id' => $schedule->id,
                    'automation_id' => $schedule->automation_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info('Scheduled automation execution complete.');
        return 0;
    }
}
