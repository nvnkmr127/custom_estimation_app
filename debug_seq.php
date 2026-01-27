<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$year = date('Y');
$prefix = "EST-{$year}-";

echo "--- Debugging Sequence Generation ---\n";
echo "Prefix: '$prefix'\n";

// 1. Dump what DB::table sees
$all = DB::table('estimates')
    ->where('estimate_number', 'like', "{$prefix}%")
    ->pluck('estimate_number');

echo "Found " . $all->count() . " records matching prefix.\n";
echo "Values:\n";
foreach ($all as $val) {
    echo " - $val\n";
}

// 2. Run the max calculation logic
$maxSequence = 0;
foreach ($all as $estNum) {
    $numStr = str_replace($prefix, '', $estNum);
    $numStr = preg_replace('/-v\d+$/', '', $numStr);

    echo "   Parsing '$estNum' -> '$numStr' -> ";
    if (is_numeric($numStr)) {
        $val = (int) $numStr;
        echo "Int: $val";
        if ($val > $maxSequence) {
            $maxSequence = $val;
            echo " (New Max)";
        }
    } else {
        echo "Not Numeric";
    }
    echo "\n";
}

echo "Calculated Max Sequence: $maxSequence\n";
echo "Next Generated: " . ($prefix . str_pad($maxSequence + 1, 3, '0', STR_PAD_LEFT)) . "\n";

// 3. Check Cache
$cacheKey = 'estimate_seq_' . $year;
echo "Cache Key '$cacheKey' value: " . (Cache::get($cacheKey) ?? 'NULL') . "\n";
