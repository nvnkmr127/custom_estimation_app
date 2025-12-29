<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_download_import_template()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        $response = $this->get(route('products.template'));

        $response->assertStatus(200);
        // Relax strict case check or just check content type part
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $response->assertHeader('Content-Disposition', 'attachment; filename="product_import_template.csv"');
    }

    public function test_can_import_products_from_csv()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        // Header now includes Image URL
        $header = "Name,SKU,Category,Base Price,Unit Type,Tags,Description,Image URL\n";
        $row1 = "Test Product 1,TP-001,Test Category,100.00,nos,tag1,Description 1,\n"; // Empty URL
        $row2 = "Test Product 2,,Test Category,150.50,sqft,,Description 2,https://invalid-url-for-test.com/image.jpg"; // URL that will fail download but shouldn't break import

        $content = $header . $row1 . $row2;

        // Put content in a real temp file to ensure it's readable
        $path = sys_get_temp_dir() . '/products.csv';
        file_put_contents($path, $content);

        $file = new UploadedFile($path, 'products.csv', 'text/csv', null, true);

        $response = $this->post(route('products.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('products.index'));

        // Check for session errors if any for debugging
        if (session('error')) {
            dump(session('error'));
        }

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product 1',
            'sku' => 'TP-001',
            'unit_price' => 100.00,
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product 2',
            'sku' => null,
            'unit_price' => 150.50,
        ]);

        $this->assertDatabaseHas('product_categories', [
            'name' => 'Test Category',
        ]);

        @unlink($path);
    }
}
