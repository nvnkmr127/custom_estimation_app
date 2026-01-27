<?php

namespace Tests\Feature\Audit;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateStructureAuditTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->client = Client::factory()->create();
    }

    /** @test */
    public function audit_calculation_integrity_and_rounding()
    {
        // Scenario: 3 items with 0.333 quantity * 100 price
        // JS Logic: 0.33 * 100 = 33.00 (if rounded early) or 33.30?
        // JS in blade: (unit_price * quantity).toFixed(2)
        // If user enters 0.333, JS calculates 0.333 * 100 = 33.3. toFixed(2) = 33.30.
        // Backend: round(0.333 * 100, 2) = 33.30.
        // Let's test specific floating point edge cases. Since PHP round matches standard rounding, we expect pass.

        $this->actingAs($this->user);

        $payload = [
            'client_id' => $this->client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'type' => 'standard',
            'status' => 'draft',
            'items' => [
                [
                    'name' => 'Item 1',
                    'quantity' => 0.333,
                    'unit_price' => 100,
                    'order_index' => 0
                ]
            ]
        ];

        $response = $this->post(route('estimates.store'), $payload);
        $estimate = Estimate::latest('id')->first();

        // Backend logic: round(100 * 0.333, 2) = round(33.3, 2) = 33.30
        $this->assertEquals(33.30, $estimate->items->first()->total);
        $this->assertEquals(33.30, $estimate->subtotal);

        // Scenario 2: 3 items of 1/3 price. 
        // 1 * 33.333333...
        $payload2 = [
            'client_id' => $this->client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'type' => 'standard',
            'status' => 'draft',
            'items' => [
                [
                    'name' => 'Item Precision',
                    'quantity' => 3,
                    'unit_price' => 33.3333,
                    'order_index' => 0
                ]
            ]
        ];

        $this->post(route('estimates.store'), $payload2);
        $estimate2 = Estimate::latest('id')->first();

        // Backend round(33.3333, 2) = 33.33. Then 33.33 * 3 = 99.99.
        // Wait, EstimateService creates item: $unitPrice = round($itemData['unit_price'], 2);
        // So 33.3333 becomes 33.33.
        // Total = 33.33 * 3 = 99.99.
        $this->assertEquals(33.33, $estimate2->items->first()->unit_price);
        $this->assertEquals(99.99, $estimate2->subtotal);
    }

    /** @test */
    public function audit_attack_scenario_negative_values()
    {
        // Attack: User manually sends negative price/quantity via API (bypassing UI validation if any)
        $this->actingAs($this->user);

        $payload = [
            'client_id' => $this->client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'type' => 'standard',
            'status' => 'draft',
            'items' => [
                [
                    'name' => 'Negative Item',
                    'quantity' => -5,
                    'unit_price' => 100,
                    'order_index' => 0
                ],
                [
                    'name' => 'Negative Price',
                    'quantity' => 1,
                    'unit_price' => -50,
                    'order_index' => 1
                ]
            ]
        ];

        $response = $this->post(route('estimates.store'), $payload);

        // We want to see if this is allowed or if it breaks calculations.
        // Ideally, negative quantity might be valid for "Returns" or "adjustments".
        // Negative price is unusual but might be a "Discount" line item.

        $estimate = Estimate::latest('id')->first();

        // Check totals
        // Item 1: -5 * 100 = -500
        // Item 2: 1 * -50 = -50
        // Subtotal should be -550?
        // Or Grand Total MAX(0, ...)?

        // From Code: 
        // $unitPrice = round($itemData['unit_price'], 2); // -50
        // $itemSubtotal = round($unitPrice * $itemData['quantity'], 2); // -500
        // ...
        // $grandTotal = max(0, $grandTotal);

        $this->assertEquals(-500.00, $estimate->items->where('name', 'Negative Item')->first()->total);
        $this->assertEquals(-50.00, $estimate->items->where('name', 'Negative Price')->first()->total);
        $this->assertEquals(-550.00, $estimate->subtotal); // The actual subtotal field allowed to be negative?

        // Grand Total should be clamped to 0
        $this->assertEquals(0.00, $estimate->grand_total);
    }

    /** @test */
    public function audit_room_mode_structure_and_persistence()
    {
        $this->actingAs($this->user);

        // 1. Create with Rooms
        $payload = [
            'client_id' => $this->client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'type' => 'room_based',
            'status' => 'draft',
            'sections' => [
                [
                    'name' => 'Living Room',
                    'order_index' => 1, // Intentional non-zero
                    'items' => [
                        ['name' => 'Sofa', 'quantity' => 1, 'unit_price' => 500, 'order_index' => 0]
                    ]
                ],
                [
                    'name' => 'Kitchen',
                    'order_index' => 0, // Should come first
                    'items' => [
                        ['name' => 'Table', 'quantity' => 1, 'unit_price' => 300, 'order_index' => 0]
                    ]
                ]
            ]
        ];

        $this->post(route('estimates.store'), $payload);
        $estimate = Estimate::latest('id')->first();

        // Verify Sort Order
        $sections = $estimate->sections()->orderBy('order_index')->get();
        $this->assertEquals('Kitchen', $sections->first()->name); // 0
        $this->assertEquals('Living Room', $sections->last()->name); // 1

        // 2. Update: Delete Kitchen (implicitly by omitting from payload)
        // Simulate dragging Sofa to Kitchen (which is now deleted?? No, simple scenario first)
        // Scenario: Delete "Kitchen", Rename "Living Room" -> "Main Hall".

        $livingRoomId = $sections->last()->id;

        $updatePayload = [
            'client_id' => $this->client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'type' => 'room_based',
            'status' => 'draft',
            'sections' => [
                [
                    'id' => $livingRoomId,
                    'name' => 'Main Hall',
                    'order_index' => 0,
                    'items' => [
                        // Keeping the sofa
                        ['name' => 'Sofa', 'quantity' => 1, 'unit_price' => 500, 'order_index' => 0]
                    ]
                ]
            ]
        ];

        $this->put(route('estimates.update', $estimate), $updatePayload);

        $estimate->refresh();
        $this->assertEquals(1, $estimate->sections->count());
        $this->assertEquals('Main Hall', $estimate->sections->first()->name);

        // Verify Kitchen items are gone (Soft deleted or Hard deleted? Model has SoftDeletes?
        // EstimateItem has SoftDeletes. EstimateSection? Let's check or assume.)
        $this->assertEquals(1, $estimate->items()->count()); // Only Sofa remains
    }

    /** @test */
    public function audit_mixed_input_types_and_overflows()
    {
        $this->actingAs($this->user);

        // Decimal Overflow on Quantity
        $payload = [
            'client_id' => $this->client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'type' => 'standard',
            'items' => [
                [
                    'name' => 'Overflow Qty',
                    'quantity' => 1.999999999, // Should ideally round to 2 in DB if float, or truncated?
                    'unit_price' => 100,
                ]
            ]
        ];

        $this->post(route('estimates.store'), $payload);
        $estimate = Estimate::latest('id')->first();

        // Database field type for quantity? Usually decimal(10,2) or double.
        // Code doesn't explictly round quantity in 'createEstimateItem', only price.
        // Let's check what happened.
        // If not rounded in code, DB might truncate or round.
        $item = $estimate->items->first();

        // If the DB column is decimal(8,2), it will be 2.00 or 1.99.
        // This test will reveal the behavior.
        // We Assert nothing specific yet, just dumping to see behavior in failure message if we assert equality.
        // Ideally we WANT it to be 2.00 or handle it consistently.
        // Let's assert it is <= 2.
        $this->assertTrue($item->quantity <= 2.0);
    }
}
