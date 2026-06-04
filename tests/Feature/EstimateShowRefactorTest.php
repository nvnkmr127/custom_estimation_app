<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Models\Estimate;
use App\Models\PdfTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateShowRefactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimate_show_page_renders_for_authorized_user()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $estimate = Estimate::factory()->create([
            'estimate_number' => 'EST-VIEW-TEST',
            'title' => 'Refactor Test',
            'created_by' => $user->id,
            'client_id' => 1 // simplified
        ]);

        $response = $this->actingAs($user)->get(route('estimates.show', $estimate));

        $response->assertStatus(200);
        $response->assertSee('EST-VIEW-TEST');
        $response->assertSee('Refactor Test');
    }

    public function test_estimate_preview_renders_template_html()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create(['name' => 'Preview Client']);
        $template = PdfTemplate::factory()->create([
            'html_content' => '<html><body><h1>Preview Estimate</h1><p>{estimate_number}</p></body></html>',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('estimates.preview'), [
            'client_id' => $client->id,
            'estimate_date' => now()->toDateString(),
            'status' => 'draft',
            'currency' => 'USD',
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'pdf_template_id' => $template->id,
            'type' => 'standard',
            'items' => [
                [
                    'name' => 'Preview Item',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'unit_type' => 'nos',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertSee('Preview Estimate');
        $response->assertSee('EST-PREVIEW');
    }

    public function test_estimate_show_page_denies_unauthorized_user()
    {
        $creator = User::factory()->create(['role' => 'estimator']);
        $otherUser = User::factory()->create(['role' => 'estimator']);

        $estimate = Estimate::factory()->create([
            'estimate_number' => 'EST-SECRET',
            'title' => 'Secret Estimate',
            'created_by' => $creator->id,
            'client_id' => 1
        ]);

        $response = $this->actingAs($otherUser)->get(route('estimates.show', $estimate));

        $response->assertStatus(403);
    }
}
