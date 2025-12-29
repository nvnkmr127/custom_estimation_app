<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display product library with search and filters
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::with('category');

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->active(); // Default to active only
        }

        // Filter by featured
        if ($request->boolean('featured')) {
            $query->featured();
        }

        // Sort
        $query->orderBy('sort_order')->orderBy('name');

        $products = $query->paginate(20);
        $categories = ProductCategory::all();
        $pendingCount = Product::pending()->count();

        return view('products.index', compact('products', 'categories', 'pendingCount'));
    }

    /**
     * Show form to create new product
     */
    public function create()
    {
        $this->authorize('create', Product::class);

        $categories = ProductCategory::all();
        return view('products.create', compact('categories'));
    }

    /**
     * Store new product
     */
    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        $validated = $this->validateProduct($request);

        $validated['status'] = 'active';
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['tags'] = $this->parseTags($validated['tags'] ?? null);

        $product = Product::create($validated);

        $this->handleImages($request, $product);
        $this->handleOptions($validated, $product);

        return redirect()->route('products.index')
            ->with('success', 'Product added successfully');
    }

    /**
     * Show form to edit product
     */
    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $categories = ProductCategory::all();
        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Update product
     */
    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $this->validateProduct($request, $product->id);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['tags'] = $this->parseTags($validated['tags'] ?? null);

        $product->update($validated);

        $this->handleImages($request, $product);

        // Sync Options: Full Replace
        $product->options()->delete();
        $this->handleOptions($validated, $product);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully');
    }

    /**
     * Delete product
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully');
    }

    /**
     * Suggest new product (team members)
     */
    public function suggest(Request $request)
    {
        // Assuming team members can suggest, so maybe specific permission or just auth check
        // For now, minimal validation and create pending product
        $validated = $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit_price' => 'required|numeric|min:0',
            'unit_type' => 'required|string',
        ]);

        Product::create(array_merge($validated, [
            'status' => 'pending',
            'suggested_by' => auth()->id(),
        ]));

        return redirect()->route('products.index')
            ->with('success', 'Product suggestion submitted for approval');
    }

    /**
     * View pending suggestions
     */
    public function pending()
    {
        // View any implies index access, but maybe pending queue is restricted
        $this->authorize('viewAny', Product::class);

        $products = Product::pending()
            ->with(['category', 'suggestedBy'])
            ->latest()
            ->paginate(20);

        return view('products.pending', compact('products'));
    }

    /**
     * Approve suggestion (admin only)
     */
    public function approve(Product $product)
    {
        $this->authorize('update', $product);

        $product->approve();

        return redirect()->route('products.pending')
            ->with('success', 'Product approved and added to library');
    }

    /**
     * Retire product (admin only)
     */
    public function retire(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'retirement_reason' => 'nullable|string|max:500',
        ]);

        $product->retire($validated['retirement_reason'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Product retired successfully',
        ]);
    }

    /**
     * Activate retired product
     */
    public function activate(Product $product)
    {
        $this->authorize('update', $product);

        $product->activate();

        return response()->json([
            'success' => true,
            'message' => 'Product reactivated successfully',
        ]);
    }

    /**
     * Download CSV Template for Bulk Upload
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product_import_template.csv"',
        ];

        $columns = ['Name', 'SKU', 'Category', 'Base Price', 'Unit Type', 'Tags', 'Description', 'Image URL'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Example Row
            fputcsv($file, ['Example Tile', 'TILE-001', 'Flooring', '12.50', 'sqft', 'premium, ceramic', 'High quality ceramic tile', 'https://example.com/image.jpg']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import Products from CSV
     */
    public function import(Request $request)
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), 'r');

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
                    continue; // Skip invalid rows
                }

                // Find or Create Category
                $category = ProductCategory::firstOrCreate(
                    ['name' => $categoryName]
                );

                // Create Product
                Product::create([
                    'name' => $name,
                    'sku' => $sku ?: null,
                    'category_id' => $category->id,
                    'unit_price' => $price,
                    'unit_type' => $unitType ?: 'nos', // Default to nos
                    'tags' => $this->parseTags($tags),
                    'description' => $description,
                    'status' => 'active',
                ]);

                $imported++;

            } catch (\Exception $e) {
                $errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }

        fclose($handle);

        if (count($errors) > 0) {
            return redirect()->route('products.index')
                ->with('success', "Imported {$imported} products.")
                ->with('error', "Some errors occurred: " . implode(', ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? '...' : ''));
        }

        return redirect()->route('products.index')
            ->with('success', "Successfully imported {$imported} products.");
    }

    // START PRIVATE HELPERS

    private function validateProduct(Request $request, $productId = null)
    {
        return $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|unique:products,sku' . ($productId ? ',' . $productId : ''),
            'unit_price' => 'required|numeric|min:0',
            'unit_type' => 'required|string',
            'calculation_method' => 'nullable|string|in:standard,formula',
            'is_featured' => 'nullable|boolean',
            'images.*' => 'nullable|image|max:5120',
            'dimensions' => 'nullable|array',
            'attributes' => 'nullable|array',
            'tags' => 'nullable|string', // Input is string "tag1, tag2"
            'options' => 'nullable|array',
            'options.*.name' => 'required|string',
            'options.*.values' => 'required|array',
            'options.*.values.*.value' => 'required|string',
            'options.*.values.*.price_adjustment' => 'nullable|numeric',
        ]);
    }

    private function parseTags($tagsString)
    {
        if (is_array($tagsString))
            return $tagsString;
        if (empty($tagsString))
            return [];
        return array_map('trim', explode(',', $tagsString));
    }

    private function handleImages(Request $request, Product $product)
    {
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products/' . $product->id, 'public');
                $product->images()->create([
                    'image_path' => $path,
                ]);
            }
        }
    }

    private function handleOptions($validated, Product $product)
    {
        if (!empty($validated['options'])) {
            foreach ($validated['options'] as $optData) {
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
}

