<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\EstimateItem;

// Update first 3 items of Estimate 22 with some size and config data
$items = EstimateItem::where('estimate_id', 22)->take(3)->get();
foreach ($items as $index => $item) {
    $item->size = "Size " . ($index + 1);
    $item->formula = "area";
    $item->length = 10 + $index;
    $item->width = 12 + $index;
    $item->save();
}

echo "Updated 3 items for Estimate 22 with size and formula data.\n";
