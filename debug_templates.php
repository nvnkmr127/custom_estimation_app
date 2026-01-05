<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$templates = App\Models\RoomTemplate::all();
foreach ($templates as $t) {
    echo "Template: " . $t->name . "\n";
    print_r($t->items);
    echo "\n-------------------\n";
}
