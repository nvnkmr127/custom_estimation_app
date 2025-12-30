<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'estimator_admin',
        ]);

        $this->category = ProductCategory::create(['name' => 'Flooring']);
    }

    public function test_can_create_product_with_all_fields()
    {
        $this->actingAs($this->user);

        Storage::fake('public');
        $image = UploadedFile::fake()->image('product.jpg');

        $payload = [
            'name' => 'Complex Product',
            'sku' => 'CP-001',
            'category_id' => $this->category->id,
            'unit_price' => 100.00,
            'unit_type' => 'sqft',
            'description' => '<p>Detailed description</p>',
            'tags' => 'tag1, tag2',
            'dimensions' => [
                'length' => 10,
                'width' => 5,
                'height' => 1,
                'unit' => 'in',
            ],
            'attributes' => [
                ['key' => 'Origin', 'value' => 'Italy'],
                ['key' => 'Finish', 'value' => 'Matte'],
            ],
            'options' => [
                [
                    'name' => 'Color',
                    'values' => [
                        ['value' => 'Red', 'price_adjustment' => 0],
                        ['value' => 'Blue', 'price_adjustment' => 5],
                    ],
                ],
                [
                    'name' => 'Size',
                    'values' => [
                        ['value' => 'Large', 'price_adjustment' => 10],
                    ],
                ],
            ],
            'images' => [$image],
            'is_featured' => true,
        ];

        $response = $this->post(route('products.store'), $payload);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'name' => 'Complex Product',
            'sku' => 'CP-001',
            'unit_price' => 100.00,
            'is_featured' => 1,
        ]);

        $product = Product::where('sku', 'CP-001')->first();

        // Check Tags
        $this->assertEquals(['tag1', 'tag2'], $product->tags);

        // Check Dimensions
        $this->assertEquals([
            'length' => 10,
            'width' => 5,
            'height' => 1,
            'unit' => 'in',
        ], $product->dimensions);

        // Check Attributes
        $this->assertEquals([
            ['key' => 'Origin', 'value' => 'Italy'],
            ['key' => 'Finish', 'value' => 'Matte'],
        ], $product->attributes);

        // Check Options
        $this->assertCount(2, $product->options);
        $colorOption = $product->options()->where('name', 'Color')->first();
        $this->assertNotNull($colorOption);
        $this->assertCount(2, $colorOption->values);

        $this->assertDatabaseHas('product_option_values', [
            'product_option_id' => $colorOption->id,
            'value' => 'Blue',
            'price_adjustment' => 5,
        ]);

        // Check Images
        $this->assertCount(1, $product->images);
        Storage::disk('public')->assertExists($product->images->first()->image_path);
    }

    public function test_can_update_product_with_all_fields()
    {
        $this->actingAs($this->user);

        $product = Product::create([
            'name' => 'Old Name',
            'sku' => 'OLD-001',
            'category_id' => $this->category->id,
            'unit_price' => 50,
            'unit_type' => 'nos',
            'tags' => ['old'],
            'attributes' => [['key' => 'Old', 'value' => 'Val']],
        ]);

        // Create update payload
        $payload = [
            'name' => 'New Name',
            'sku' => 'OLD-001',
            'category_id' => $this->category->id,
            'unit_price' => 75,
            'unit_type' => 'nos',
            'tags' => 'newtag',
            'dimensions' => ['length' => 1, 'unit' => 'm'],
            'attributes' => [['key' => 'New', 'value' => 'Val']],
            'options' => [
                [
                    'name' => 'Material',
                    'values' => [['value' => 'Steel', 'price_adjustment' => 0]],
                ],
            ],
            // We are NOT sending images
        ];

        $response = $this->put(route('products.update', $product), $payload);

        $response->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertEquals('New Name', $product->name);
        $this->assertEquals(['newtag'], $product->tags);
        $this->assertEquals(['length' => 1, 'unit' => 'm'], $product->dimensions);
        $this->assertEquals([['key' => 'New', 'value' => 'Val']], $product->attributes);

        $this->assertCount(1, $product->options);
        $this->assertEquals('Material', $product->options->first()->name);
    }
}
