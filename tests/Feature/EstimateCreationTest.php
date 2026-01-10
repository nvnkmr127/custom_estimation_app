<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Estimate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_estimate_creation_works_with_retry_logic()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();

        $data = [
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
                    'name' => 'Item 1',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'unit_type' => 'hrs',
                    'tax_1' => 0,
                    'tax_2' => 0,
                    'order_index' => 0
                ]
            ]
        ];

        $response = $this->actingAs($user)->post(route('estimates.store'), $data);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $estimate = Estimate::latest('id')->first();
        $this->assertNotNull($estimate);
        $this->assertEquals($client->id, $estimate->client_id);
    }
}
