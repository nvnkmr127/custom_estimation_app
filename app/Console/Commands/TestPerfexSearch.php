<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PerfexApiService;

class TestPerfexSearch extends Command
{
    protected $signature = 'perfex:test-search {query}';
    protected $description = 'Test Perfex Search API';

    public function handle(PerfexApiService $api)
    {
        $query = $this->argument('query');
        $this->info("Searching for: $query");

        try {
            $start = microtime(true);
            $results = $api->searchLeads($query);
            $duration = microtime(true) - $start;

            $this->info("Request took: " . round($duration, 2) . "s");

            if (isset($results['error']) || (isset($results['status']) && $results['status'] === false)) {
                $this->error("API Error: " . json_encode($results));
                return;
            }

            $this->info("Results found: " . count($results));
            $this->line(json_encode($results, JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage());
        }
    }
}
