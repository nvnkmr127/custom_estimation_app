<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Estimate;
use App\Services\EstimateService;

$estimate = Estimate::find(22);
if ($estimate) {
    // We need to resolve EstimateService which requires EventDispatcher
    $service = app(EstimateService::class);
    $service->recalculateTotals($estimate);
    echo "Recalculated totals for Estimate #{$estimate->estimate_number}.\n";

    // Check if sections now have subtotals
    foreach ($estimate->sections()->get() as $section) {
        echo "Section: {$section->name}, Subtotal: {$section->subtotal}\n";
    }
} else {
    echo "Estimate 22 not found.\n";
}
