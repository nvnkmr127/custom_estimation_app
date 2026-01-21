<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Automation;
use Carbon\Carbon;

class AnalyticsSeeder extends Seeder
{
    public function run()
    {
        $automation = Automation::first();
        if (!$automation) {
            echo "No automation found. Please create one first.\n";
            return;
        }

        $data = [];
        $now = Carbon::today();

        for ($i = 30; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $total = rand(50, 200);
            $failed = rand(0, 10);
            $successful = $total - $failed;

            $data[] = [
                'automation_id' => $automation->id,
                'date' => $date->format('Y-m-d'),
                'total_executions' => $total,
                'successful_executions' => $successful,
                'failed_executions' => $failed,
                'total_duration_ms' => $total * rand(100, 500),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('automation_daily_stats')->insertOrIgnore($data);
        echo "Seeded analytics data for automation: {$automation->name}\n";
    }
}
