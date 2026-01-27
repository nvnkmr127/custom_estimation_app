<?php

use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Client;
use App\Services\EstimateService;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Bypass Log Permission Issues
try {
    Log::swap(new \Illuminate\Log\Logger(new \Monolog\Logger('null', [new \Monolog\Handler\NullHandler()])));
} catch (\Throwable $t) {
}

// Helper to assert
function assertCalc($desc, $expected, $actual)
{
    $pass = abs($expected - $actual) < 0.01;
    echo $pass ? "[PASS] $desc\n" : "[FAIL] $desc (Expected: $expected, Got: $actual)\n";
}

function assertVisibility($desc, $object, $forbiddenField)
{
    $array = $object->toArray();
    $pass = !array_key_exists($forbiddenField, $array);
    echo $pass ? "[PASS] $desc (Field '$forbiddenField' hidden)\n" : "[FAIL] $desc (Field '$forbiddenField' EXPOSED)\n";
}

echo "Starting QA Calculation Audit...\n";

$service = app(EstimateService::class);
$user = \App\Models\User::first(); // Assume a user exists
auth()->login($user);

DB::beginTransaction();

try {
    // Setup Client
    $client = Client::firstOrCreate([
        'name' => 'QA Test Client',
        'email' => 'qa@test.com'
    ]);

    // --- CASE 1: Standard Calculation Order ---
    // Item 1: 10 * 10.55 = 105.50
    // Item 2: 2 * 5.00 = 10.00
    // Subtotal: 115.50
    // Discount: 10% = 11.55
    // Taxable: 103.95
    // Tax 1 (10%): 10.395 -> 10.40
    // Total: 103.95 + 10.40 = 114.35

    echo "\n--- CASE 1: Standard Calculation Order ---\n";
    $data = [
        'client_id' => $client->id,
        'estimate_date' => now(),
        'expiry_date' => now()->addDays(7),
        'tax_1' => 10,
        'tax_2' => 0,
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'title' => 'QA Test 1',
        'status' => 'draft'
    ];

    $items = [
        [
            'name' => 'Item 1',
            'quantity' => 10,
            'unit_price' => 10.55,
            'cost' => 5.00, // Margin check
            'tax_1' => 0, // Ignored by Service currently? (Global override)
        ],
        [
            'name' => 'Item 2',
            'quantity' => 2,
            'unit_price' => 5.00,
            'cost' => 2.00,
        ]
    ];

    $estimate = $service->createEstimate($data, $items, 'standard');
    $estimate->refresh();

    assertCalc("Subtotal", 115.50, $estimate->subtotal);
    assertCalc("Discount", 11.55, $estimate->discount_total);
    // Taxable (Implicit check via tax)
    // 115.50 - 11.55 = 103.95
    // Tax = 10.40 (rounded from 10.395)
    assertCalc("Total Tax", 10.40, $estimate->total_tax);
    assertCalc("Grand Total", 114.35, $estimate->grand_total);

    // --- CASE 2: Fixed Discount > Subtotal ---
    echo "\n--- CASE 2: Discount > Subtotal ---\n";
    $data2 = $data;
    $data2['discount_type'] = 'fixed';
    $data2['discount_value'] = 200.00; // More than 115.50

    $estimate2 = $service->createEstimate($data2, $items, 'standard');

    // Subtotal: 115.50
    // Discount: Should match subtotal? or Cap? Service line 400: min($discountTotal, $subtotal)
    // So Discount = 115.50
    // Tax Base = 0
    // Tax = 0
    // Total = 0

    assertCalc("Capped Discount", 115.50, $estimate2->discount_total);
    assertCalc("Zero Tax", 0.00, $estimate2->total_tax);
    assertCalc("Zero Total", 0.00, $estimate2->grand_total);


    // --- CASE 3: Gross Tax Mode (Tax on Subtotal, ignore Discount) ---
    echo "\n--- CASE 3: Gross Tax Mode ---\n";
    Setting::set('tax_calculation_method', 'subtotal_only');

    // Re-run Case 1 Logic
    // Subtotal: 115.50
    // Discount (10%): 11.55
    // Taxable: 115.50 (Gross)
    // Tax (10%): 11.55
    // Total: (115.50 - 11.55) + 11.55 = 115.50

    // We need to trigger recalculation or create new.
    // Create new for cleanliness (Service fetches setting fresh?)
    // Setting::getCached uses Cache, so we might need to clear cache or ensure non-cached read.
    Cache::forget('settings.all');
    Cache::forget('setting.tax_calculation_method'); // assuming key

    $estimate3 = $service->createEstimate($data, $items, 'standard'); // Same data as Case 1

    assertCalc("Gross Tax Amount", 11.55, $estimate3->total_tax);
    assertCalc("Grand Total (Net + Gross Tax)", 115.50, $estimate3->grand_total);

    // Restore Setting
    Setting::set('tax_calculation_method', 'subtotal_minus_discount');

    // --- CASE 4: Security Visibility ---
    echo "\n--- CASE 4: Visibility Checks ---\n";
    // Check Case 1 Estimate
    assertVisibility("Estimate Cost Hidden", $estimate, 'total_cost');
    assertVisibility("Estimate Profit Hidden", $estimate, 'gross_profit');
    assertVisibility("Estimate Admin Note Hidden", $estimate, 'admin_note');

    // Check Item
    // Note: EstimateItem already has $hidden = ['cost', 'internal_note'] defined in class (checked in Step 10)
    $item = $estimate->items->first();
    assertVisibility("Item Cost Hidden", $item, 'cost');
    assertVisibility("Item Internal Note Hidden", $item, 'internal_note');

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

DB::rollBack();
echo "\nTest Run Complete (Rolled Back).\n";
