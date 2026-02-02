<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Services\PerfexApiService;
use Illuminate\Support\Facades\Log;

class SyncPerfexLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'perfex:sync-leads {--status-id= : The ID of the status to filter by}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull leads from Perfex with status "Open Hot" and sync to Clients.';

    /**
     * Execute the console command.
     */
    public function handle(PerfexApiService $api)
    {
        $this->info("=== PERFEX LEAD SYNC ===");
        $openHotId = $this->option('status-id');

        // 1. Discovery Phase (if ID not provided)
        if (!$openHotId) {
            $this->info("No Status ID provided. Attempting discovery...");

            // A. Try Search
            $this->info("Method A: Searching for 'Open Hot'...");
            $searchResults = $api->searchLeads('Open Hot');

            if (is_array($searchResults) && !isset($searchResults['error']) && !isset($searchResults['status'])) {
                foreach ($searchResults as $lead) {
                    if (strcasecmp($lead['status_name'] ?? '', 'Open Hot') === 0 && isset($lead['status'])) {
                        $openHotId = $lead['status'];
                        $this->info("SUCCESS: Found Status ID via Search: $openHotId");
                        break;
                    }
                }
            }

            // B. Try Zapier Endpoint (lighter fetch)
            if (!$openHotId) {
                $this->info("Method B: Polling via Zapier endpoint (limit=50)...");
                $zapierLeads = $api->getLeadsViaZapier(50);

                if (is_array($zapierLeads) && !isset($zapierLeads['error']) && !isset($zapierLeads['status'])) {
                    foreach ($zapierLeads as $lead) {
                        if (strcasecmp($lead['status_name'] ?? '', 'Open Hot') === 0 && isset($lead['status'])) {
                            $openHotId = $lead['status'];
                            $this->info("SUCCESS: Found Status ID via Zapier Poll: $openHotId");
                            break;
                        }
                    }
                }
            }
        } else {
            $this->info("Using manually provided Status ID: $openHotId");
        }

        if (!$openHotId) {
            $this->error("Could not discover 'Open Hot' status ID. Please provide it manually using --status-id=ID");
            return;
        }

        // 2. Sync Phase
        $this->info("Fetching leads with Status ID: $openHotId");
        // We use the regular getLeads but now we have a status filter
        $leads = $api->getLeads(100, true, ['status' => $openHotId]);

        if (isset($leads['status']) && $leads['status'] === false) {
            $this->warn("Main getLeads endpoint failed (500/Timeout). Falling back to Search 'Open Hot'...");
            $leads = $api->searchLeads('Open Hot');
        }

        if (isset($leads['status']) && $leads['status'] === false) {
            $this->error("Failed to fetch leads: " . ($leads['message'] ?? 'Unknown Error'));
            return;
        }

        if (empty($leads)) {
            $this->warn("No leads found for status $openHotId.");
            return;
        }

        $this->info("Found " . count($leads) . " leads. Syncing...");
        foreach ($leads as $lead) {
            $this->syncLead($lead, $api);
        }

        $this->info("=== SYNC COMPLETE ===");
    }

    protected function syncLead($data, $api)
    {
        $perfexId = $data['id'] ?? null;
        $email = $data['email'] ?? null;

        if (!$perfexId)
            return;

        // Find existing client by Perfex ID or Email
        $client = Client::where('perfex_id', $perfexId)->first();

        if (!$client && $email) {
            $client = Client::where('email', $email)->first();
        }

        if (!$client) {
            $this->info("Creating new client for: " . ($data['name'] ?? 'Unknown'));
            $client = new Client();
        } else {
            $this->info("Updating existing client: " . $client->name);
        }

        // Basic fields for creation if they don't exist yet
        $client->perfex_id = $perfexId;
        if (!$client->name)
            $client->name = $data['name'] ?? 'Unknown';
        if (!$client->email)
            $client->email = $email;
        $client->save(); // Save first to get ID if new

        // Update API Service to handle data sync
        // We will call a modified fetchAndSyncClient that accepts data
        $api->fetchAndSyncClient($client, $data);
    }
}
