<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class PingPerfexCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'perfex:cron-ping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ping the Perfex CRM cron URL to trigger its internal tasks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = Setting::getCached('perfex_cron_url', 'https://crm.onestudio.co.in/cron/index');

        $this->info("Pinging Perfex Cron: {$url}");

        try {
            $response = Http::timeout(120)->get($url);

            if ($response->successful()) {
                $this->info("Cron triggered successfully.");
                Log::info("Perfex Cron: Successfully pinged {$url}");
            } else {
                $this->error("Cron request failed with status: " . $response->status());
                Log::error("Perfex Cron: Failed to ping {$url}. Status: " . $response->status());
            }
        } catch (\Exception $e) {
            $this->error("Error pinging cron: " . $e->getMessage());
            Log::error("Perfex Cron: Exception during ping to {$url}: " . $e->getMessage());
        }
    }
}
