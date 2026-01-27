<?php

use Illuminate\Support\Facades\DB;
use App\Models\Estimate;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Schema Debugger ---\n";

try {
    // 1. Check DB Driver
    $conn = DB::connection();
    echo "DB Driver: " . $conn->getDriverName() . "\n";
    echo "Database: " . $conn->getDatabaseName() . "\n";

    // 2. List Indexes (SQLite vs MySQL)
    if ($conn->getDriverName() === 'sqlite') {
        $indexes = DB::select("PRAGMA index_list('estimates')");
        echo "\nIndexes on 'estimates':\n";
        foreach ($indexes as $idx) {
            echo "- " . $idx->name . " (Unique: " . $idx->unique . ")\n";
            $cols = DB::select("PRAGMA index_info('" . $idx->name . "')");
            foreach ($cols as $col) {
                echo "    Column: " . $col->name . "\n";
            }
        }
    } elseif ($conn->getDriverName() === 'mysql') {
        $indexes = DB::select("SHOW INDEX FROM estimates");
        echo "\nIndexes on 'estimates':\n";
        foreach ($indexes as $idx) {
            echo "- Key: " . $idx->Key_name . ", Column: " . $idx->Column_name . ", Non_unique: " . $idx->Non_unique . "\n";
        }
    }

    // 3. Test Insert with Random Number
    echo "\n--- Test Insert ---\n";
    $randomNum = 'TEST-' . uniqid();
    echo "Attempting insert with number: $randomNum\n";

    // Minimal data
    $data = [
        'estimate_number' => $randomNum,
        'client_id' => 1, // Assumption: client 1 exists? Or mock it.
        'estimate_date' => date('Y-m-d'),
        'status' => 'draft',
        'type' => 'standard',
        'created_by' => 1
    ];

    // Verify client exists
    $client = DB::table('clients')->first();
    if ($client) {
        $data['client_id'] = $client->id;
    } else {
        echo "WARNING: No clients found. Using ID 1 (might fail FK).\n";
    }

    Estimate::create($data);
    echo "SUCCESS: Inserted $randomNum\n";

    // Cleanup
    Estimate::where('estimate_number', $randomNum)->forceDelete();
    echo "Cleaned up.\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
