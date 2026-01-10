<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendDigestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-digests {frequency=daily_digest}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send aggregated notification digests to users';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\Notifications\DigestService $digestService)
    {
        $frequency = $this->argument('frequency');

        $this->info("Starting digest delivery for frequency: $frequency");

        $digestService->sendDigests($frequency);

        $this->info("Digest delivery complete.");
    }
}
