<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\PdfTemplate;

$seeder = new \Database\Seeders\PdfTemplateSeeder();
$seeder->run();

echo "PDF Templates synced from seeder successfully.\n";
