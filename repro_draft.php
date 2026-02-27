<?php

use App\Models\Estimate;
use App\Models\User;
use App\Services\EstimateService;
use Illuminate\Support\Facades\Auth;

// Require the bootstrap file
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Login as super admin
Auth::login(User::first());

$service = app(EstimateService::class);

// 1. Create a DRAFT estimate
echo "Creating a DRAFT estimate...\n";
$data = [
    'client_id' => 1,
    'estimate_date' => now()->format('Y-m-d'),
    'status' => 'draft',
    'currency' => 'USD',
    'type' => 'standard'
];
$items = [
    [
        'name' => 'Initial Item',
        'unit_price' => 50,
        'quantity' => 1,
        'unit_type' => 'nos'
    ]
];

$estimate = $service->createEstimate($data, $items, 'standard');
echo "Created Draft Estimate ID: {$estimate->id}\n";

// 2. Update it with a NEW item
echo "Updating the DRAFT estimate with a NEW item...\n";
$updateData = $data;
$updateItems = [
    [
        'id' => $estimate->items->first()->id,
        'name' => 'Initial Item',
        'unit_price' => 50,
        'quantity' => 1,
        'unit_type' => 'nos'
    ],
    [
        'id' => null,
        'name' => 'NEW TEST ITEM',
        'unit_price' => 100,
        'quantity' => 2,
        'unit_type' => 'nos'
    ]
];

try {
    $updated = $service->updateEstimate($estimate, $updateData, $updateItems, 'standard');
    echo "Updated Estimate ID: {$updated->id}\n";

    if ($updated->id !== $estimate->id) {
        echo "WARNING: Branched when it should have updated in place!\n";
    }

    $updated->load('items');
    $found = $updated->items->where('name', 'NEW TEST ITEM')->first();

    if ($found) {
        echo "SUCCESS: NEW TEST ITEM found (ID: {$found->id})\n";
    } else {
        echo "FAILURE: NEW TEST ITEM NOT FOUND!\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
