<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display product library with search and filters.
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
     * Show form to create new product.
     */
    public function create()
    {
        $this->authorize('create', Product::class);

        $categories = ProductCategory::all();

        return view('products.create', compact('categories'));
    }

    /**
     * Store new product.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        $validated = $this->validateProduct($request);

        try {
            $this->productService->createProduct($validated, $request);

            return redirect()->route('products.index')
                ->with('success', 'Product added successfully');
        } catch (\Exception $e) {
            Log::error('Failed to store product', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors(['error' => 'Failed to add product: ' . $e->getMessage()]);
        }
    }

    /**
     * Show form to edit product.
     */
    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $categories = ProductCategory::all();

        // Prepare complex data for the view
        $productAttributes = json_encode($product->attributes ?? []);
        $productOptions = json_encode($product->options->load('values')->map(function ($opt) {
            return [
                'name' => $opt->name,
                'values' => $opt->values->map(function ($v) {
                    return ['value' => $v->value, 'price_adjustment' => $v->price_adjustment];
                })
            ];
        }));
        $productTags = is_array($product->tags) ? implode(',', $product->tags) : ($product->tags ?? '');

        return view('products.edit', compact('product', 'categories', 'productAttributes', 'productOptions', 'productTags'));
    }

    /**
     * Update product.
     */
    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $this->validateProduct($request, $product->id);

        try {
            $this->productService->updateProduct($product, $validated, $request);

            return redirect()->route('products.index')
                ->with('success', 'Product updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update product', [
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete product.
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully');
    }

    /**
     * Suggest new product (team members).
     */
    public function suggest(Request $request)
    {
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
     * View pending suggestions.
     */
    public function pending()
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::pending()
            ->with(['category', 'suggestedBy'])
            ->latest()
            ->paginate(20);

        return view('products.pending', compact('products'));
    }

    /**
     * Approve suggestion (admin only).
     */
    public function approve(Product $product)
    {
        $this->authorize('update', $product);

        $product->approve();

        return redirect()->route('products.pending')
            ->with('success', 'Product approved and added to library');
    }

    /**
     * Retire product (admin only).
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
     * Activate retired product.
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
     * Download CSV Template for Bulk Upload.
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
     * Import Products from CSV.
     */
    public function import(Request $request)
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        try {
            $result = $this->productService->importFromCsv($request->file('csv_file')->getPathname());

            if (count($result['errors']) > 0) {
                Log::warning('Product import completed with errors', [
                    'user_id' => auth()->id(),
                    'imported_count' => $result['imported_count'],
                    'error_count' => count($result['errors']),
                ]);

                return redirect()->route('products.index')
                    ->with('success', "Imported {$result['imported_count']} products.")
                    ->with('error', 'Some errors occurred: ' . implode(', ', array_slice($result['errors'], 0, 3)));
            }

            return redirect()->route('products.index')
                ->with('success', "Successfully imported {$result['imported_count']} products.");
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate product input.
     */
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
            'tags' => 'nullable|string',
            'options' => 'nullable|array',
            'options.*.name' => 'required|string',
            'options.*.values' => 'required|array',
            'options.*.values.*.value' => 'required|string',
            'options.*.values.*.price_adjustment' => 'nullable|numeric',
        ]);
    }
}
