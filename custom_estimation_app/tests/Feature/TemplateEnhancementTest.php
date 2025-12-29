<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RoomTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateEnhancementTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_template_page_loads_with_products()
    {
        $user = User::factory()->create(['role' => 'estimator_admin']);
        $product = Product::create([
            'name' => 'Test Product',
            'unit_price' => 100,
            'unit_type' => 'units',
            'sku' => 'TP-001'
        ]);

        $response = $this->actingAs($user)->get(route('templates.create'));

        $response->assertStatus(200);
        $response->assertViewHas('products');
        $response->assertSee('Test Product');
    }

    public function test_can_create_template_with_product_link()
    {
        $user = User::factory()->create(['role' => 'estimator_admin']);
        $product = Product::create([
            'name' => 'Linked Product',
            'unit_price' => 100,
            'unit_type' => 'units',
            'sku' => 'TP-002'
        ]);

        $response = $this->actingAs($user)->post(route('templates.store'), [
            'name' => 'Enhanced Template',
            'description' => 'Testing product links',
            'items' => [
                [
                    'item_name' => $product->name,
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                    'unit_type' => 'nos'
                ]
            ]
        ]);

        $response->assertRedirect(route('templates.index'));

        $template = RoomTemplate::where('name', 'Enhanced Template')->first();
        $this->assertNotNull($template);
        $this->assertEquals($product->id, $template->items[0]['product_id']);
    }
}
