<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Parse tags string into an array.
     */
    public function parseTags($tagsString): array
    {
        if (is_array($tagsString)) {
            return $tagsString;
        }
        if (empty($tagsString)) {
            return [];
        }

        return array_map('trim', explode(',', $tagsString));
    }

    /**
     * Handle product image uploads.
     *
     * @param  array|\Illuminate\Http\UploadedFile[]  $images
     */
    public function handleImages(array $images, Product $product): void
    {
        foreach ($images as $image) {
            // Check if it's an uploaded file instance
            if ($image instanceof \Illuminate\Http\UploadedFile) {
                $path = $image->store('products/' . $product->id, 'public');
                $product->images()->create([
                    'image_path' => $path,
                ]);
            }
        }
    }

    /**
     * Handle product options and their values.
     */
    public function handleOptions(array $options, Product $product): void
    {
        if (!empty($options)) {
            foreach ($options as $optData) {
                // Skip empty names
                if (empty($optData['name'])) {
                    continue;
                }

                $option = $product->options()->create(['name' => $optData['name']]);

                if (isset($optData['values']) && is_array($optData['values'])) {
                    foreach ($optData['values'] as $valData) {
                        if (empty($valData['value'])) {
                            continue;
                        }
                        $option->values()->create([
                            'value' => $valData['value'],
                            'price_adjustment' => $valData['price_adjustment'] ?? 0,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Import products from a CSV file.
     */
    public function importFromCsv($filePath): array
    {
        $handle = fopen($filePath, 'r');
        // Skip header
        fgetcsv($handle);

        $imported = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Ensure row has enough columns (basic check)
            if (count($row) < 5) {
                continue;
            }

            try {
                // Map columns: 0=Name, 1=SKU, 2=Category, 3=Price, 4=Unit, 5=Tags, 6=Desc
                $name = trim($row[0]);
                $sku = trim($row[1] ?? '');
                $categoryName = trim($row[2]);
                $price = floatval(trim($row[3]));
                $unitType = strtolower(trim($row[4]));
                $tags = trim($row[5] ?? '');
                $description = trim($row[6] ?? '');
                $imageUrl = trim($row[7] ?? '');

                if (empty($name) || empty($categoryName)) {
                    continue;
                }

                // Data Integrity: Normalize category and basic fields
                $categoryName = ucwords(strtolower(trim($categoryName)));
                
                // Security: Basic Remote Image Verification
                if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $imageUrl = null;
                }

                $category = ProductCategory::firstOrCreate(['name' => $categoryName]);

                // Create Product
                $product = Product::create([
                    'name' => $name,
                    'sku' => $sku ?: null,
                    'category_id' => $category->id,
                    'unit_price' => $price,
                    'unit_type_id' => null, // Default to manual
                    'unit_type' => $unitType ?: 'nos',
                    'tags' => $this->parseTags($tags),
                    'description' => $description,
                    'status' => 'active',
                ]);

                if ($imageUrl) {
                    $product->images()->create([
                        'image_path' => $imageUrl,
                    ]);
                }

                $imported++;

            } catch (\Exception $e) {
                $errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }

        fclose($handle);

        return [
            'imported_count' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * Create a new product with images and options.
     */
    public function createProduct(array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($data, $images) {
            // Set Defaults
            $data['status'] = $data['status'] ?? 'active';
            $data['is_featured'] = isset($data['is_featured']) ? (bool) $data['is_featured'] : false;

            // Parse tags if string
            if (isset($data['tags'])) {
                $data['tags'] = $this->parseTags($data['tags']);
            }

            $product = Product::create($data);

            if (!empty($images)) {
                $this->handleImages($images, $product);
            }

            if (!empty($data['options'])) {
                $this->handleOptions($data['options'], $product);
            }

            return $product;
        });
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(Product $product, array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($product, $data, $images) {
            if (isset($data['is_featured'])) {
                $data['is_featured'] = (bool) $data['is_featured'];
            }

            if (isset($data['tags'])) {
                $data['tags'] = $this->parseTags($data['tags']);
            }

            $product->update($data);

            if (!empty($images)) {
                $this->handleImages($images, $product);
            }

            // Sync Options: Full Replace
            $product->options()->delete();
            if (!empty($data['options'])) {
                $this->handleOptions($data['options'], $product);
            }

            return $product;
        });
    }
}
