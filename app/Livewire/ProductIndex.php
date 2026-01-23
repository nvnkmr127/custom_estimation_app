<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage; // Added for deleteImage method
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads; // Added

class ProductIndex extends Component
{
    use WithPagination, WithFileUploads; // Modified

    public $search = '';
    public $category = '';
    public $status = '';
    public $viewMode = 'table'; // 'table' or 'grid'

    // For modals
    public $showSuggestModal = false;
    public $showRetireModal = false;
    public $showUploadModal = false;
    public $showEditModal = false;

    // For editing/creating
    public $editingProduct = null;
    public $productForm = [
        'id' => null,
        'name' => '',
        'sku' => '',
        'category_id' => '',
        'unit_price' => '',
        'unit_type' => 'nos',
        'unit_type_id' => '', // Added
        'description' => '',
        'status' => 'active',
        'calculation_method' => 'standard',
        'is_featured' => false,
        'tax_1' => 0, // Added
        'dimensions' => [ // Added
            'length' => '',
            'width' => '',
            'height' => '',
            'unit' => 'in'
        ],
        'tags' => '', // Added
        'formula' => '', // Added
    ];

    public $options = []; // Added
    public $extraAttributes = []; // Added
    public $newImages = []; // Added
    public $existingImages = []; // Added

    public $retireReason = '';
    public $activeProductId = null;
    public $isEditing = false; // Added

    protected $queryString = [
        'search' => ['except' => ''],
        'category' => ['except' => ''],
        'status' => ['except' => ''],
        'viewMode' => ['except' => 'table'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategory()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'category', 'status']);
        $this->resetPage();
    }

    public function updatedProductFormUnitTypeId($value)
    {
        if ($value) {
            $unitType = \App\Models\UnitType::find($value);
            if ($unitType && !empty($unitType->units)) {
                $this->productForm['unit_type'] = $unitType->units[0];
            }
        }
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    // Dynamic List Management
    public function addOptionGroup()
    {
        $this->options[] = ['name' => '', 'values' => [['value' => '', 'price_adjustment' => 0]]];
    }

    public function removeOptionGroup($index)
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
    }

    public function addOptionValue($groupIndex)
    {
        $this->options[$groupIndex]['values'][] = ['value' => '', 'price_adjustment' => 0];
    }

    public function removeOptionValue($groupIndex, $valueIndex)
    {
        unset($this->options[$groupIndex]['values'][$valueIndex]);
        $this->options[$groupIndex]['values'] = array_values($this->options[$groupIndex]['values']);
    }

    public function addAttribute()
    {
        $this->extraAttributes[] = ['key' => '', 'value' => ''];
    }

    public function removeAttribute($index)
    {
        unset($this->extraAttributes[$index]);
        $this->extraAttributes = array_values($this->extraAttributes);
    }

    public function setEditing($value) // Added
    {
        $this->isEditing = $value;
        if ($value) {
            // Re-init Quill when switching to edit mode if needed
            $this->dispatch('init-quill');
        }
    }

    public function editProduct($productId = null)
    {
        $this->reset(['editingProduct', 'productForm', 'options', 'extraAttributes', 'newImages', 'existingImages']); // Modified

        if ($productId) {
            $this->editingProduct = Product::with(['images', 'options.values'])->findOrFail($productId); // Modified
            $this->productForm = [
                'id' => $this->editingProduct->id,
                'name' => $this->editingProduct->name,
                'sku' => $this->editingProduct->sku,
                'category_id' => $this->editingProduct->category_id,
                'unit_price' => $this->editingProduct->unit_price,
                'unit_type' => $this->editingProduct->unit_type,
                'unit_type_id' => $this->editingProduct->unit_type_id, // Added
                'description' => $this->editingProduct->description,
                'status' => $this->editingProduct->status,
                'calculation_method' => $this->editingProduct->calculation_method,
                'is_featured' => (bool) $this->editingProduct->is_featured,
                'tax_1' => $this->editingProduct->tax_1 ?? 0, // Added
                'dimensions' => $this->editingProduct->dimensions ?? ['length' => '', 'width' => '', 'height' => '', 'unit' => 'in'], // Added
                'tags' => is_array($this->editingProduct->tags) ? implode(', ', $this->editingProduct->tags) : ($this->editingProduct->tags ?? ''), // Added
                'formula' => $this->editingProduct->formula ?? '', // Added
            ];
            $this->isEditing = false; // View mode by default for existing products

            // Populate options
            foreach ($this->editingProduct->options as $option) {
                $values = [];
                foreach ($option->values as $val) {
                    $values[] = [
                        'value' => $val->value,
                        'price_adjustment' => $val->price_adjustment,
                    ];
                }
                $this->options[] = [
                    'name' => $option->name,
                    'values' => $values,
                ];
            }

            // Populate attributes
            if (is_array($this->editingProduct->attributes)) {
                foreach ($this->editingProduct->attributes as $key => $value) {
                    $this->extraAttributes[] = ['key' => $key, 'value' => $value];
                }
            }

            // Populate existing images
            foreach ($this->editingProduct->images as $img) {
                $this->existingImages[] = [
                    'id' => $img->id,
                    'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($img->image_path),
                ];
            }
        } else {
            // Default values for new product
            $this->productForm = [
                'id' => null,
                'name' => '',
                'sku' => '',
                'category_id' => '',
                'unit_price' => '',
                'unit_type' => 'nos',
                'unit_type_id' => '', // Added
                'description' => '',
                'status' => 'active',
                'calculation_method' => 'standard',
                'is_featured' => false,
                'tax_1' => 0,
                'dimensions' => ['length' => '', 'width' => '', 'height' => '', 'unit' => 'in'],
                'tags' => '',
                'formula' => '',
            ];
            $this->isEditing = true; // Edit mode by default for new products
        }

        $this->showEditModal = true;
    }

    public function saveProduct(\App\Services\ProductService $productService)
    {
        $rules = [
            'productForm.name' => 'required|string|max:255',
            'productForm.sku' => 'nullable|string|max:100|unique:products,sku,' . ($this->productForm['id'] ?? 'NULL'),
            'productForm.category_id' => 'required|exists:product_categories,id',
            'productForm.unit_price' => 'required|numeric|min:0',
            'productForm.unit_type' => 'required|string',
            'productForm.unit_type_id' => 'nullable|exists:unit_types,id',
            'productForm.status' => 'required|in:active,retired,pending',
            'productForm.tax_1' => 'nullable|numeric|min:0|max:100',
            'productForm.dimensions.length' => 'nullable|numeric|min:0',
            'productForm.dimensions.width' => 'nullable|numeric|min:0',
            'productForm.dimensions.height' => 'nullable|numeric|min:0',
            'productForm.dimensions.unit' => 'nullable|string|in:in,ft,cm,m,mm',
            'productForm.tags' => 'nullable|string|max:500',
            'productForm.formula' => 'required_if:productForm.calculation_method,formula|nullable|string|max:1000',
            'options.*.name' => 'nullable|string|max:255',
            'options.*.values.*.value' => 'nullable|string|max:255',
            'options.*.values.*.price_adjustment' => 'nullable|numeric',
            'extraAttributes.*.key' => 'nullable|string|max:255',
            'extraAttributes.*.value' => 'nullable|string|max:255',
            'newImages.*' => 'nullable|image|max:2048', // 2MB Max
        ];

        try {
            $this->validate($rules);
            \Log::info('Validation passed', ['form' => $this->productForm]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', ['errors' => $e->errors()]);
            throw $e;
        }

        try {
            // Process attributes
            $attributesMap = [];
            foreach ($this->extraAttributes as $attr) {
                if (!empty($attr['key'])) {
                    $attributesMap[$attr['key']] = $attr['value'];
                }
            }

            // Prepare Data for Service
            $data = $this->productForm;
            // Tags extraction is handled by Service if string, but we are passing string here. Service handles it.
            // But we need to ensure the service receives what it expects.
            // Service::parseTags handles "string" or "array".
            // Livewire model is string.

            $data['attributes'] = $attributesMap;
            // Map options to structure expected by Service
            // Service expects array of ['name' => ..., 'values' => [...]]
            // Livewire $this->options matches this structure.
            $data['options'] = $this->options;

            if ($this->productForm['id']) {
                $product = Product::find($this->productForm['id']);
                $productService->updateProduct($product, $data, $this->newImages);
                \Log::info('Product updated', ['id' => $product->id]);
                session()->flash('success', 'Product updated successfully');
            } else {
                $product = $productService->createProduct($data, $this->newImages);
                \Log::info('Product created', ['id' => $product->id]);
                session()->flash('success', 'Product created successfully');
            }

        } catch (\Exception $e) {
            \Log::error('Error saving product', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Critical Error: ' . $e->getMessage());
            return;
        }

        $this->showEditModal = false;
        $this->reset(['editingProduct', 'productForm', 'options', 'extraAttributes', 'newImages', 'existingImages']);
    }

    public function deleteImage($imageId) // Added
    {
        $image = ProductImage::findOrFail($imageId);
        \Illuminate\Support\Facades\Storage::disk('public')->delete($image->image_path);
        $image->delete();

        $this->existingImages = array_filter($this->existingImages, fn($img) => $img['id'] != $imageId);
    }

    public function retireProduct($productId, $reason = null)
    {
        $product = Product::findOrFail($productId);
        $product->retire($reason);
        $this->showRetireModal = false;
        $this->reset(['retireReason', 'activeProductId']);

        session()->flash('success', 'Product retired successfully');
    }

    public function activateProduct($productId)
    {
        $product = Product::findOrFail($productId);
        $product->activate();

        session()->flash('success', 'Product reactivated successfully');
    }

    public function render()
    {
        $query = Product::with(['category', 'images']);

        if (!empty($this->search)) {
            $query->search($this->search);
        }

        if (!empty($this->category)) {
            $query->where('category_id', $this->category);
        }

        if (!empty($this->status)) {
            $query->where('status', $this->status);
        } else {
            $query->active();
        }

        $query->orderBy('sort_order')->orderBy('name');

        return view('livewire.product-index', [
            'products' => $query->paginate(20),
            'categories' => ProductCategory::all(),
            'unitTypes' => \App\Models\UnitType::all(), // Added
            'pendingCount' => Product::pending()->count(),
        ]);
    }
}
