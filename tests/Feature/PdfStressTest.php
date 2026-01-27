<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\PdfTemplate;
use App\Models\Product;
use App\Models\User;
use App\Services\PdfRenderingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PdfStressTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        // Create necessary data
        $this->user = User::factory()->create();
        $this->client = Client::factory()->create();
    }

    public function test_large_estimate_pdf_performance()
    {
        // 1. Create Estimate with 100 items
        $estimate = Estimate::create([
            'estimate_number' => 'STRESS-001',
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'estimate_date' => now(),
            'status' => 'draft',
            'subtotal' => 10000,
            'grand_total' => 10000,
        ]);

        $items = [];
        for ($i = 0; $i < 100; $i++) {
            $items[] = [
                'name' => "Stress Item $i",
                'description' => "This is a description for item $i to add some bulk text to the PDF rendering process.",
                'quantity' => 1,
                'unit_price' => 100,
                'total' => 100,
                'cost' => 50, // Sensitive Data
                'order_index' => $i,
            ];
        }

        $estimate->items()->createMany($items);

        $template = PdfTemplate::create([
            'name' => 'Stress Template',
            'html_content' => '
                <h1>Estimate {estimate_number}</h1>
                {LOOP_ITEMS}
                    <div class="item">
                        <b>{item_name}</b> - {item_price}
                        <p>{item_description}</p>
                    </div>
                {END_LOOP}
                <div class="totals">
                    Grand Total: {grand_total}
                </div>
            ',
            'is_active' => true,
        ]);

        // 2. Measure PDF Generation Time
        $start = microtime(true);
        $service = new PdfRenderingService();
        $path = $service->renderAndCache($template, $estimate);
        $end = microtime(true);
        $duration = $end - $start;

        echo "\nPDF Generation Time (100 items): " . number_format($duration, 4) . "s\n";

        $this->assertLessThan(5.0, $duration, "PDF generation took too long (> 5s)");
        $this->assertFileExists($path); // Should technically return a path
    }

    public function test_cost_field_leakage_prevention()
    {
        // 1. Create Estimate with known sensitive cost
        $sensitiveCost = 1234.56;
        $estimate = Estimate::create([
            'estimate_number' => 'LEAK-001',
            'created_by' => $this->user->id,
            'status' => 'draft',
        ]);

        $estimate->items()->create([
            'name' => 'Item 1',
            'quantity' => 1,
            'unit_price' => 2000,
            'cost' => $sensitiveCost, // THIS SHOULD NOT APPEAR IN PDF
        ]);

        // 2. Create a malicious template trying to access cost
        $template = PdfTemplate::create([
            'name' => 'Hacker Template',
            'html_content' => '
                Info: {item_name}
                Price: {item_price}
                Cost: {item_cost} 
                Raw Cost: {_raw_cost}
                Item Cost: {cost}
                Profit: {profit}
                Margin: {margin}
            ',
            'is_active' => true,
        ]);

        // 3. Render
        $service = new PdfRenderingService();
        // We use render() directly to get HTML string for assertion
        $html = $service->render($template, $estimate);

        // 4. Assert
        $this->assertStringNotContainsString((string) $sensitiveCost, $html, "Sensitive COST data leaked in PDF HTML!");
        // The variables {item_cost} should remain as literal strings or be empty, but definitely NOT the value.
    }

    public function test_portal_magic_link_access()
    {
        $estimate = Estimate::create([
            'estimate_number' => 'MAGIC-001',
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'status' => 'sent', // Must be sent to be viewable
        ]);

        // Generate Signed URL
        $url = URL::signedRoute('portal.show', $estimate);

        // Visit as Guest
        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('portal.estimates.show');
        $this->assertDatabaseHas('estimates', [
            'id' => $estimate->id,
            'view_count' => 1,
        ]);
    }

    public function test_portal_prevents_unauthorized_download()
    {
        $estimate = Estimate::create([
            'estimate_number' => 'SECURE-001',
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'status' => 'sent',
        ]);

        // Attempt to access download route typically meant for auth users
        // Assuming /estimates/{id}/download logic
        $response = $this->get(route('estimates.download', $estimate));
        // Should redirect to login or 403
        $response->assertStatus(302); // Redirect to login
    }
}
