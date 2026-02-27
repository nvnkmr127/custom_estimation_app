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

// Get an existing estimate
$estimate = Estimate::where('type', 'room_based')->first();
if (!$estimate) {
    echo "No room_based estimate found to test.\n";
    exit;
}

echo "Testing Update for Estimate #{$estimate->estimate_number} (ID: {$estimate->id})\n";

// Login as the creator
Auth::login(User::find($estimate->created_by));

$service = app(EstimateService::class);

// Prepare dummy data for update
$data = [
    'client_id' => $estimate->client_id,
    'estimate_date' => (is_string($estimate->estimate_date) ? $estimate->estimate_date : $estimate->estimate_date->format('Y-m-d')),
    'status' => 'draft',
    'currency' => $estimate->currency,
    'discount_type' => 'percentage',
    'discount_value' => 0,
    'pdf_template_id' => $estimate->pdf_template_id,
    'type' => 'room_based'
];

// Add a NEW item to the first section
$sections = [];
foreach ($estimate->sections as $s) {
    $items = [];
    foreach ($s->items as $i) {
        $items[] = [
            'id' => $i->id,
            'name' => $i->name,
            'unit_price' => $i->unit_price,
            'quantity' => $i->quantity,
            'unit_type' => $i->unit_type,
            'unit_type_id' => $i->unit_type_id,
        ];
    }
    // Add a NEW item
    $items[] = [
        'id' => null,
        'name' => 'NEW TEST ITEM',
        'unit_price' => 100,
        'quantity' => 1,
        'unit_type' => 'nos',
        'unit_type_id' => null,
    ];

    $sections[] = [
        'id' => $s->id,
        'name' => $s->name,
        'items' => $items
    ];
}

try {
    echo "Calling updateEstimate...\n";
    $updated = $service->updateEstimate($estimate, $data, $sections, 'room_based');
    echo "Updated Estimate ID: {$updated->id}\n";

    $updated->load('sections.items');
    $found = false;
    foreach ($updated->sections as $s) {
        foreach ($s->items as $i) {
            if ($i->name === 'NEW TEST ITEM') {
                echo "SUCCESS: NEW TEST ITEM found in section '{$s->name}'\n";
                $found = true;
            }
        }
    }

    if (!$found) {
        echo "FAILURE: NEW TEST ITEM NOT FOUND!\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
