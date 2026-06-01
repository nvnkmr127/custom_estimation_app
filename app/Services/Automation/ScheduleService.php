<?php

namespace App\Services\Automation;

use App\Models\AutomationSchedule;
use Carbon\Carbon;
use Cron\CronExpression;

class ScheduleService
{
    /**
     * Parse and validate a cron expression.
     */
    public function parseCronExpression(string $cron): array
    {
        try {
            $expression = new CronExpression($cron);

            return [
                'valid' => true,
                'expression' => $cron,
                'parts' => [
                    'minute' => $expression->getExpression(CronExpression::MINUTE),
                    'hour' => $expression->getExpression(CronExpression::HOUR),
                    'day' => $expression->getExpression(CronExpression::DAY),
                    'month' => $expression->getExpression(CronExpression::MONTH),
                    'weekday' => $expression->getExpression(CronExpression::WEEKDAY),
                ],
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate a cron expression.
     */
    public function validateCronExpression(string $cron): bool
    {
        try {
            new CronExpression($cron);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Calculate the next run time for a cron expression in a specific timezone.
     */
    public function calculateNextRun(string $cron, string $timezone = 'UTC'): Carbon
    {
        $expression = new CronExpression($cron);
        $now = Carbon::now($timezone);

        // Get next run time in the specified timezone
        $nextRun = Carbon::instance($expression->getNextRunDate($now->toDateTime()));

        // Convert to UTC for storage
        return $nextRun->setTimezone('UTC');
    }

    /**
     * Get a human-readable description of the schedule.
     */
    public function getHumanReadableSchedule(string $cron): string
    {
        try {
            $expression = new CronExpression($cron);

            // Common patterns
            $patterns = [
                '* * * * *' => 'Every minute',
                '*/5 * * * *' => 'Every 5 minutes',
                '*/15 * * * *' => 'Every 15 minutes',
                '*/30 * * * *' => 'Every 30 minutes',
                '0 * * * *' => 'Every hour',
                '0 0 * * *' => 'Daily at midnight',
                '0 9 * * *' => 'Daily at 9:00 AM',
                '0 9 * * 1-5' => 'Weekdays at 9:00 AM',
                '0 0 * * 0' => 'Weekly on Sunday',
                '0 0 1 * *' => 'Monthly on the 1st',
            ];

            if (isset($patterns[$cron])) {
                return $patterns[$cron];
            }

            // Build description from parts
            $parts = $this->parseCronExpression($cron);
            if (!$parts['valid']) {
                return 'Invalid schedule';
            }

            $description = 'At ';

            // Hour and minute
            if ($parts['parts']['minute'] === '*' && $parts['parts']['hour'] === '*') {
                $description = 'Every minute';
            } elseif ($parts['parts']['minute'] !== '*' && $parts['parts']['hour'] !== '*') {
                $description .= sprintf('%02d:%02d', $parts['parts']['hour'], $parts['parts']['minute']);
            } elseif ($parts['parts']['hour'] !== '*') {
                $description .= 'hour ' . $parts['parts']['hour'];
            }

            // Day of week
            if ($parts['parts']['weekday'] !== '*') {
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $weekdayParts = explode(',', $parts['parts']['weekday']);
                $dayNames = array_map(fn($d) => $days[$d] ?? $d, $weekdayParts);
                $description .= ' on ' . implode(', ', $dayNames);
            }

            return $description;
        } catch (\InvalidArgumentException $e) {
            return 'Invalid schedule';
        }
    }

    /**
     * Update the next run time for a schedule.
     */
    public function updateNextRunTime(AutomationSchedule $schedule): void
    {
        $nextRun = $this->calculateNextRun($schedule->cron_expression, $schedule->timezone);
        $schedule->update(['next_run_at' => $nextRun]);
    }

    /**
     * Get schedules that are due to run.
     */
    public function getDueSchedules(): \Illuminate\Database\Eloquent\Collection
    {
        return AutomationSchedule::where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->with('automation')
            ->get();
    }

    /**
     * Get common cron presets.
     */
    public function getPresets(): array
    {
        return [
            [
                'label' => 'Every minute',
                'value' => '* * * * *',
                'description' => 'Runs every minute',
            ],
            [
                'label' => 'Every 5 minutes',
                'value' => '*/5 * * * *',
                'description' => 'Runs every 5 minutes',
            ],
            [
                'label' => 'Every 15 minutes',
                'value' => '*/15 * * * *',
                'description' => 'Runs every 15 minutes',
            ],
            [
                'label' => 'Every 30 minutes',
                'value' => '*/30 * * * *',
                'description' => 'Runs every 30 minutes',
            ],
            [
                'label' => 'Hourly',
                'value' => '0 * * * *',
                'description' => 'Runs at the start of every hour',
            ],
            [
                'label' => 'Daily at 9 AM',
                'value' => '0 9 * * *',
                'description' => 'Runs every day at 9:00 AM',
            ],
            [
                'label' => 'Weekdays at 9 AM',
                'value' => '0 9 * * 1-5',
                'description' => 'Runs Monday through Friday at 9:00 AM',
            ],
            [
                'label' => 'Weekly on Monday',
                'value' => '0 9 * * 1',
                'description' => 'Runs every Monday at 9:00 AM',
            ],
            [
                'label' => 'Monthly on 1st',
                'value' => '0 9 1 * *',
                'description' => 'Runs on the 1st of every month at 9:00 AM',
            ],
        ];
    }
}
