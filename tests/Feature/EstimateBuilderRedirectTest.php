<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateBuilderRedirectTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function store_json_returns_redirect_url_to_show()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();

        $response = $this->actingAs($user)->postJson(route('estimates.store'), [
            'client_id' => $client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'expiry_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'draft',
            'currency' => 'USD',
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'type' => 'standard',
            'items' => [
                [
                    'name' => 'Line Item',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'unit_type' => 'nos',
                    'tax_1' => 0,
                    'tax_2' => 0,
                    'order_index' => 0,
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $estimateId = $response->json('estimate_id');
        $this->assertNotNull($estimateId);

        $response->assertJson([
            'redirect_url' => route('estimates.show', $estimateId),
        ]);
    }

    /** @test */
    public function update_json_returns_redirect_url_to_show()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();

        $estimate = Estimate::factory()->create([
            'created_by' => $user->id,
            'client_id' => $client->id,
            'estimate_status' => Estimate::EST_STATUS_DRAFT,
            'is_current_version' => true,
            'currency' => 'USD',
        ]);

        $response = $this->actingAs($user)->putJson(route('estimates.update', $estimate), [
            'title' => 'Updated Title',
            'client_id' => $client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'expiry_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => Estimate::EST_STATUS_DRAFT,
            'currency' => 'USD',
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'type' => 'standard',
            'items' => [
                [
                    'name' => 'Line Item',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'unit_type' => 'nos',
                    'tax_1' => 0,
                    'tax_2' => 0,
                    'order_index' => 0,
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $updatedId = $response->json('estimate_id');
        $this->assertNotNull($updatedId);

        $response->assertJson([
            'redirect_url' => route('estimates.show', $updatedId),
        ]);
    }
}

