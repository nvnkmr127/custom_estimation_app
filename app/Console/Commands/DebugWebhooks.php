<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\Automation;
use Illuminate\Support\Facades\DB;
use App\Core\Events\Estimates\EstimateCreated;

class DebugWebhooks extends Command
{
    protected $signature = 'debug:webhooks';
    protected $description = 'Check webhook system health and configuration';

    public function handle()
    {
        $this->header("Webhook System Diagnosis");

        // 1. Queue Status
        $queueConn = config('queue.default');
        $jobCount = DB::table('jobs')->count();
        $this->info("Queue Connection: {$queueConn}");
        if ($jobCount > 0) {
            $this->warn("!!! Warning: There are {$jobCount} pending jobs in the queue. If no queue worker is running, webhooks will NEVER be sent.");
            $this->line("Run 'php artisan queue:work' to process them.");
        } else {
            $this->info("Queue is empty.");
        }

        // 2. Global Webhook Settings
        $this->newLine();
        $this->header("Global Webhook Settings");
        $approvalUrl = Setting::getCached('estimate_approval_webhook_url');
        $clientUrl = Setting::getCached('estimate_client_webhook_url');

        $this->checkSetting('estimate_approval_webhook_url', $approvalUrl);
        $this->checkSetting('estimate_client_webhook_url', $clientUrl);

        if (!$approvalUrl && !$clientUrl) {
            $this->error("No global webhook URLs configured. Global webhooks will be skipped.");
        }

        // 3. Automation Engine
        $this->newLine();
        $this->header("Automation Engine Status");
        $activeAutomations = Automation::active()->count();
        $this->info("Active Automations: {$activeAutomations}");

        if ($activeAutomations === 0) {
            $this->warn("No active automations found. Webhooks triggered via automation rules will not fire.");
        } else {
            $webhookActions = DB::table('automation_actions')->where('type', 'webhook')->count();
            $this->info("Automations with Webhook Actions: {$webhookActions}");
        }

        // 4. Test Dispatch
        $this->newLine();
        if ($this->confirm('Would you like to dispatch a test EstimateCreated event? (Logs will appear in laravel.log)', false)) {
            $estimate = \App\Models\Estimate::first();
            if ($estimate) {
                event(new EstimateCreated($estimate, $estimate->created_by ?? 1));
                $this->info("Test event dispatched.");
                $this->line("Check storage/logs/laravel.log for 'EventDispatcher: Dispatched to Laravel'");
            } else {
                $this->error("No estimates found in database to use for test event.");
            }
        }
    }

    protected function header($text)
    {
        $this->line("<options=bold;fg=cyan># {$text}</>");
    }

    protected function checkSetting($key, $value)
    {
        if ($value) {
            $this->info("✓ {$key}: {$value}");
        } else {
            $this->line("<fg=red>✗ {$key}: NOT CONFIGURED</>");
        }
    }
}
