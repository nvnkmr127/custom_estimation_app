<?php

namespace Tests\Feature;

use App\Models\CouponCode;
use App\Models\User;
use App\Models\Client;
use App\Models\Estimate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ajax_create_returns_estimate_object_so_subsequent_saves_can_update_in_place()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();

        $createPayload = [
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
                    'order_index' => 0,
                ],
            ],
        ];

        $createResponse = $this->actingAs($user)->postJson(route('estimates.store'), $createPayload);

        $createResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'estimate_id',
                'estimate_number',
                'estimate' => [
                    'id',
                    'items',
                ],
                'redirect_url',
                'last_update_timestamp',
            ]);

        $this->assertSame(1, Estimate::count());

        $estimateId = $createResponse->json('estimate.id');
        $this->assertNotNull($estimateId);
        $this->assertSame($createResponse->json('estimate_id'), $estimateId);

        $itemId = $createResponse->json('estimate.items.0.id');
        $this->assertNotNull($itemId);

        $updatePayload = $createPayload;
        $updatePayload['items'][0]['id'] = $itemId;
        $updatePayload['items'][0]['unit_price'] = 125;

        $updateResponse = $this->actingAs($user)->putJson(route('estimates.update', $estimateId), $updatePayload);

        $updateResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, Estimate::count());
        $this->assertDatabaseHas('estimates', ['id' => $estimateId]);
    }

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

    public function test_coupon_validation_returns_discount_amount()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $coupon = CouponCode::create([
            'code' => 'SAVE10',
            'name' => 'Save 10',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('coupons.validate'), [
            'code' => 'SAVE10',
            'total' => 1000,
        ]);

        $response->assertOk()
            ->assertJson([
                'valid' => true,
                'coupon_id' => $coupon->id,
                'discount' => 100.0,
            ]);
    }

    public function test_estimate_calculation_returns_manual_discount_and_coupon_discount_separately()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->postJson(route('estimates.calculate'), [
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'coupon_discount' => 25,
            'tax_1' => 5,
            'items' => [
                [
                    'name' => 'Item',
                    'unit_price' => 100,
                    'quantity' => 2,
                    'unit_type' => 'nos',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'subtotal' => 200.0,
                'discount_total' => 20.0,
                'coupon_discount' => 25.0,
                'grand_total' => 164.0,
            ]);
    }

    private function creationPayload(Client $client, string $status): array
    {
        return [
            'client_id' => $client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'expiry_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => $status,
            'currency' => 'USD',
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'type' => 'standard',
            'items' => [
                ['name' => 'Item 1', 'quantity' => 1, 'unit_price' => 100, 'unit_type' => 'hrs', 'tax_1' => 0, 'tax_2' => 0, 'order_index' => 0],
            ],
        ];
    }

    /** @test */
    public function estimator_admin_can_create_estimate_in_non_draft_status()
    {
        // Consistency with UpdateEstimateRequest: estimator_admin is an admin here too.
        $admin = User::factory()->create(['role' => 'estimator_admin']);
        $client = Client::factory()->create();

        $this->actingAs($admin)
            ->postJson(route('estimates.store'), $this->creationPayload($client, 'sent'))
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function plain_estimator_cannot_create_estimate_in_non_draft_status()
    {
        $estimator = User::factory()->create(['role' => 'estimator']);
        $client = Client::factory()->create();

        $this->actingAs($estimator)
            ->postJson(route('estimates.store'), $this->creationPayload($client, 'sent'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }
}
