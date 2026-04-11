<?php

namespace App\Console\Commands;

use App\Models\Estimate;
use App\Models\NurtureRule;
use App\Models\Setting;
use App\Notifications\EstimateNurtureNotification;
use App\Services\RescueNegotiationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunEstimateNurture extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estimates:nurture {--dry-run : Run without sending emails or applying changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process intelligent nurturing rules for estimates';

    /**
     * Execute the console command.
     */
    public function handle(RescueNegotiationService $rescueService)
    {
        $isActive = Setting::getCached('nurture_system_active', false);
        if (!$isActive) {
            $this->info('Nurture system is disabled in settings.');
            return;
        }

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info('[DRY RUN] No actions will be executed.');
        }

        $rules = NurtureRule::active()->get();
        $this->info("Found {$rules->count()} active rules.");

        foreach ($rules as $rule) {
            $this->processRule($rule, $rescueService, $dryRun);
        }
    }

    protected function processRule(NurtureRule $rule, RescueNegotiationService $rescueService, bool $dryRun)
    {
        $this->info("Processing Rule: {$rule->name}");

        $query = Estimate::where('status', 'sent')
            ->where('nurture_status', 'active'); // Only nurture active ones

        // --- Trigger Logic ---
        if ($rule->trigger_type === 'time_based') {
            // e.g. 3 days after created/sent
            $targetDate = now()->subDays($rule->trigger_days)->toDateString();
            $query->whereDate('created_at', '<=', $targetDate);

        } elseif ($rule->trigger_type === 'engagement_based') {
            // e.g. 4 days since last view
            $targetDate = now()->subDays($rule->trigger_days)->toDateString();
            $query->whereNotNull('last_viewed_at')
                ->whereDate('last_viewed_at', '<=', $targetDate);

        } elseif ($rule->trigger_type === 'expiry_based') {
            // e.g. 3 days BEFORE expiry
            $targetDate = now()->addDays($rule->trigger_days)->toDateString();
            $query->whereDate('expiry_date', $targetDate);
        }

        $estimates = $query->get();
        $this->info("  Found {$estimates->count()} matching estimates.");

        foreach ($estimates as $estimate) {
            // --- Atomic Rule Tracking ---
            // Check if THIS specific rule was already fired for THIS estimate to avoid spam/redundancy
            $alreadyFired = \App\Models\ActivityLog::where('subject_type', Estimate::class)
                ->where('subject_id', $estimate->id)
                ->where('action', 'nurture_applied')
                ->where('description', 'LIKE', "%[Rule ID: {$rule->id}]%")
                ->exists();

            if ($alreadyFired) {
                continue;
            }

            // Skip if client data is missing (prevents crash on notification)
            if (!$estimate->client) {
                $this->warn("    Skipping Estimate #{$estimate->estimate_number}: Client not found.");
                continue;
            }

            // --- Condition Logic (JSON) ---
            if (!$this->evaluateConditions($estimate, $rule->condition_logic)) {
                continue;
            }

            if ($dryRun) {
                $this->info("    [DRY RUN] Would execute {$rule->action_type} for Estimate #{$estimate->estimate_number}");
                continue;
            }

            // --- Action Logic ---
            try {
                $this->executeAction($estimate, $rule, $rescueService);

                // Update State and Log Execution
                $estimate->update(['last_nurtured_at' => now()]);

                \App\Models\ActivityLog::create([
                    'action' => 'nurture_applied',
                    'description' => "Applied nurture rule '{$rule->name}' [Rule ID: {$rule->id}]. Resulted in {$rule->action_type}.",
                    'subject_type' => Estimate::class,
                    'subject_id' => $estimate->id,
                ]);

            } catch (\Exception $e) {
                Log::error("Nurture Error for Estimate #{$estimate->estimate_number}: " . $e->getMessage());
                $this->error("    Error: {$e->getMessage()}");
            }
        }
    }

    protected function evaluateConditions(Estimate $estimate, ?array $conditions): bool
    {
        if (empty($conditions)) {
            return true;
        }

        // Simple evaluation: "view_count": 0 or "view_count": ">0"
        foreach ($conditions as $key => $value) {
            $estimateValue = $estimate->{$key} ?? null;

            if ($value === '>0') {
                if (!($estimateValue > 0))
                    return false;
            } elseif ($value === '0') {
                if (!($estimateValue == 0))
                    return false;
            } else {
                if ($estimateValue != $value)
                    return false;
            }
        }

        return true;
    }

    protected function executeAction(Estimate $estimate, NurtureRule $rule, RescueNegotiationService $rescueService)
    {
        switch ($rule->action_type) {
            case 'email':
                $content = $rule->action_data ?? [];
                // Defaults
                $content['subject'] = $content['subject'] ?? 'Checking in on your estimate';
                $estimate->client->notify(new EstimateNurtureNotification($estimate, $content));
                $this->info("    Sent Email to {$estimate->client->email}");
                break;

            case 'auto_discount':
                $discount = $rule->action_data['discount_percent'] ?? 5;
                $newVersion = $rescueService->attemptRescue($estimate, $discount);
                if ($newVersion) {
                    $this->info("    Rescued! Created {$newVersion->estimate_number}");
                    // Stop nurturing the old one
                    $estimate->update(['nurture_status' => 'completed']);
                }
                break;

            case 'alert_staff':
                // Send HotLeadAlert (reused class or new one)
                // For now, reuse HotLeadAlert or just log
                $this->info("    Alerted Staff (Logged Only for now)");
                break;
        }
    }
}
