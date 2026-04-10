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
}
