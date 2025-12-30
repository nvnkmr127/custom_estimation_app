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
     */
    public function handleImages(Request $request, Product $product): void
    {
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products/'.$product->id, 'public');
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
        if (! empty($options)) {
            foreach ($options as $optData) {
                $option = $product->options()->create(['name' => $optData['name']]);
                foreach ($optData['values'] as $valData) {
                    $option->values()->create([
                        'value' => $valData['value'],
                        'price_adjustment' => $valData['price_adjustment'] ?? 0,
                    ]);
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

                if (empty($name) || empty($categoryName)) {
                    continue;
                }

                DB::transaction(function () use ($name, $sku, $categoryName, $price, $unitType, $tags, $description, &$imported) {
                    // Find or Create Category
                    $category = ProductCategory::firstOrCreate(['name' => $categoryName]);

                    // Create Product
                    Product::create([
                        'name' => $name,
                        'sku' => $sku ?: null,
                        'category_id' => $category->id,
                        'unit_price' => $price,
                        'unit_type' => $unitType ?: 'nos',
                        'tags' => $this->parseTags($tags),
                        'description' => $description,
                        'status' => 'active',
                    ]);

                    $imported++;
                });

            } catch (\Exception $e) {
                $errors[] = "Row {$rowNumber}: ".$e->getMessage();
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
    public function createProduct(array $data, Request $request): Product
    {
        return DB::transaction(function () use ($data, $request) {
            $data['status'] = $data['status'] ?? 'active';
            $data['is_featured'] = $request->boolean('is_featured');
            $data['tags'] = $this->parseTags($data['tags'] ?? null);

            $product = Product::create($data);

            $this->handleImages($request, $product);
            if (! empty($data['options'])) {
                $this->handleOptions($data['options'], $product);
            }

            return $product;
        });
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(Product $product, array $data, Request $request): Product
    {
        return DB::transaction(function () use ($product, $data, $request) {
            $data['is_featured'] = $request->boolean('is_featured');
            $data['tags'] = $this->parseTags($data['tags'] ?? null);

            $product->update($data);

            $this->handleImages($request, $product);

            // Sync Options: Full Replace
            $product->options()->delete();
            if (! empty($data['options'])) {
                $this->handleOptions($data['options'], $product);
            }

            return $product;
        });
    }
}
