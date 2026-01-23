<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Estimate;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

try {
    echo "Starting Create Version Test...\n";

    // Login as a user
    $user = User::first();
    Auth::login($user);
    echo "Logged in as user ID: {$user->id}\n";

    // Find an estimate
    $estimate = Estimate::latest()->first();
    if (!$estimate) {
        die("No estimate found.\n");
    }
    echo "Using estimate ID: {$estimate->id} (Version: {$estimate->version})\n";

    // Mock EstimateService
    $service = app(\App\Services\EstimateService::class);
    
    // Create Controller
    $controller = new \App\Http\Controllers\EstimateController($service, app(\App\Core\Events\EventDispatcherInterface::class));

    // Call createVersion
    echo "Calling createVersion on controller...\n";
    $response = $controller->createVersion($estimate);

    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        echo "Success! Returned RedirectResponse.\n";
        echo "Target URL: " . $response->getTargetUrl() . "\n";
    } else {
        echo "Failed! Did not return RedirectResponse.\n";
        var_dump($response);
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
