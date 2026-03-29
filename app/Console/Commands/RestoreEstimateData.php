<?php

namespace App\Console\Commands;

use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\EstimateSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RestoreEstimateData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estimate:restore {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactively restore a specific version of soft-deleted data for an estimate';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('id');
        $estimate = Estimate::withTrashed()->find($id);

        if (!$estimate) {
            $this->error("Estimate ID {$id} not found in the database.");
            return Command::FAILURE;
        }

        // Get unique deletion timestamps, sorted most recent first
        // We cast to string for reliable comparison
        $itemTimestamps = EstimateItem::onlyTrashed()
            ->where('estimate_id', $id)
            ->pluck('deleted_at')
            ->map(fn($t) => $t ? $t->toDateTimeString() : null)
            ->filter()
            ->unique();

        $sectionTimestamps = EstimateSection::onlyTrashed()
            ->where('estimate_id', $id)
            ->pluck('deleted_at')
            ->map(fn($t) => $t ? $t->toDateTimeString() : null)
            ->filter()
            ->unique();
        
        $allTimestamps = $itemTimestamps->merge($sectionTimestamps)
            ->unique()
            ->values()
            ->sortDesc()
            ->values();

        if ($allTimestamps->isEmpty()) {
            $this->error("No soft-deleted sections or items found for Estimate #{$estimate->estimate_number}.");
            return Command::FAILURE;
        }

        $this->info("--------------------------------------------------");
        $this->info("ESTIMATE: #{$estimate->estimate_number} (ID: {$id})");
        $this->info("--------------------------------------------------");
        $this->info("Identified deletion events (most recent first):");
        
        foreach ($allTimestamps as $index => $timestamp) {
            $itemCount = EstimateItem::onlyTrashed()
                ->where('estimate_id', $id)
                ->where('deleted_at', $timestamp)
                ->count();
            
            $sectionCount = EstimateSection::onlyTrashed()
                ->where('estimate_id', $id)
                ->where('deleted_at', $timestamp)
                ->count();
            
            $label = ($index === 0) ? " [MOST RECENT]" : (($index === 1) ? " [LAST 2nd]" : "");
            $this->line("[" . ($index + 1) . "] {$timestamp} -- {$sectionCount} rooms, {$itemCount} items {$label}");
        }
        $this->info("--------------------------------------------------");

        $choice = $this->ask("Which deletion event number do you want to restore?", 1);
        $selectedIndex = (int)$choice - 1;

        if (!isset($allTimestamps[$selectedIndex])) {
            $this->error("Invalid selection. Selection must be between 1 and " . count($allTimestamps));
            return Command::FAILURE;
        }

        $selectedTime = $allTimestamps[$selectedIndex];
        $this->info("Starting restoration of data deleted at: {$selectedTime}...");

        try {
            DB::beginTransaction();

            $restoredSections = EstimateSection::onlyTrashed()
                ->where('estimate_id', $id)
                ->where('deleted_at', $selectedTime)
                ->restore();

            $restoredItems = EstimateItem::onlyTrashed()
                ->where('estimate_id', $id)
                ->where('deleted_at', $selectedTime)
                ->restore();

            // Also restore the Estimate itself if it was soft-deleted at that time
            if ($estimate->deleted_at && $estimate->deleted_at->toDateTimeString() === $selectedTime) {
                $estimate->restore();
                $this->info("- Restored the Estimate itself.");
            }

            DB::commit();

            $this->info("--------------------------------------------------");
            $this->info("SUCCESS!");
            $this->info("- Restored Sections: {$restoredSections}");
            $this->info("- Restored Items: {$restoredItems}");
            $this->info("--------------------------------------------------");
            $this->warn("CRITICAL: You MUST REFRESH your browser page to see the");
            $this->warn("restored items before saving again.");
            $this->info("--------------------------------------------------");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("FAILED: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
