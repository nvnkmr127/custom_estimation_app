<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create a dummy product
$product = Product::create([
    'name' => 'Test Image Product',
    'unit_price' => 10,
    'status' => 'active'
]);

echo "Created product with ID: " . $product->id . "\n";

// Create a dummy image
$file = UploadedFile::fake()->image('test_product_image.jpg');

// Use ProductService to handle image
$service = new \App\Services\ProductService();
$service->handleImages([$file], $product);

// Check if image record created
$imageRecord = $product->images()->first();
if ($imageRecord) {
    echo "Image record created in DB: " . $imageRecord->image_path . "\n";

    // Check if file exists in storage
    if (Storage::disk('public')->exists($imageRecord->image_path)) {
        echo "File exists in disk('public'): " . Storage::disk('public')->path($imageRecord->image_path) . "\n";

        // Generate URL
        $url = Storage::disk('public')->url($imageRecord->image_path);
        echo "Generated URL: " . $url . "\n";
    } else {
        echo "File NOT FOUND in disk('public')\n";
    }
} else {
    echo "Image record NOT created in DB\n";
}

// Cleanup
$product->delete();
