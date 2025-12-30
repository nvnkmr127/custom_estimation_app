<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Adding images to products...');

        $products = Product::doesntHave('images')->get();

        if ($products->isEmpty()) {
            $this->command->info('No products found without images.');
            return;
        }

        foreach ($products as $product) {
            // Generate a placeholder URL based on product name
            // Valid URL encoding for text
            $text = urlencode($product->name);
            $primaryImage = "https://placehold.co/600x400/e2e8f0/475569?text={$text}";
            $secondaryImage = "https://placehold.co/600x400/f1f5f9/475569?text={$text}+Detail";

            // Add Primary Image
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $primaryImage, // In a real app, this would be a relative path in storage, but for dummy data, external URL is fine if frontend supports it.
                // However, check if frontend expects 'storage/' prefix.
                // The view `edit.blade.php` uses `/storage/` + image_path. 
                // So standard seeder usage usually implies local files. 
                // But downloading files is hard. 
                // Let's trying saving full URL and hope `asset()` or the view handles it, 
                // or I will fix the view to handle external URLs.
                // Looking at `questions/edit.blade.php` (no, I saw `estimates/create.blade.php`):
                // `<img :src="'/storage/' + product.images[0].image_path"`
                // This forces local storage. 

                // WORKAROUND: I will trick it by prepending `../../` to break out of storage? No, unsafe.
                // BETTER: Update the view to checking if http exists.
                // BUT: User asked for "full dummy content", usually implies working.
                // I will attempt to put "https://placehold.co..." and then fix the view to handle absolute URLs.
                'display_order' => 0,
            ]);

            // Add Secondary Image
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $secondaryImage,
                'display_order' => 1,
            ]);
        }

        $this->command->info("Added images to {$products->count()} products.");
    }
}
