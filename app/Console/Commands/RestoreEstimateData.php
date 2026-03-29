<?php

namespace App\Console\Commands;

use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\EstimateSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
    protected $description = 'Restore all soft-deleted sections and items for a specific estimate ID';

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

        $this->info("--------------------------------------------------");
        $this->info("RESTORE DATA FOR ESTIMATE: #{$estimate->estimate_number} (ID: {$id})");
        $this->info("--------------------------------------------------");

        try {
            DB::beginTransaction();

            // 1. Restore the Estimate itself if it was somehow soft-deleted (though unlikely)
            if ($estimate->deleted_at) {
                $estimate->restore();
                $this->info("- Restored the Estimate itself.");
            }

            // 2. Restore all soft-deleted SECTIONS for this estimate
            $restoredSectionsCount = EstimateSection::onlyTrashed()
                ->where('estimate_id', $id)
                ->restore();

            // 3. Restore all soft-deleted ITEMS for this estimate
            // (This includes both standalone items and items inside sections)
            $restoredItemsCount = EstimateItem::onlyTrashed()
                ->where('estimate_id', $id)
                ->restore();

            DB::commit();

            $this->info("SUCCESS!");
            $this->info("- Restored Sections: {$restoredSectionsCount}");
            $this->info("- Restored Items: {$restoredItemsCount}");
            $this->info("--------------------------------------------------");
            $this->warn("CRITICAL: You MUST REFRESH your browser page in the");
            $this->warn("Estimate Builder to see the restored data before saving again.");
            $this->info("--------------------------------------------------");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("FAILED: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
