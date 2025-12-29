<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_images_when_creating_product()
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'super_admin']);
        $category = ProductCategory::factory()->create();

        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Test Product',
            'category_id' => $category->id,
            'unit_price' => 100,
            'unit_type' => 'nos',
            'images' => [$file],
        ]);

        $response->assertRedirect(route('products.index'));

        $product = Product::first();
        $this->assertCount(1, $product->images);

        $imagePath = $product->images->first()->image_path;
        Storage::disk('public')->assertExists($imagePath);
    }
}
