<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateUpdateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_updates_estimate_without_destroying_existing_items()
    {
        // 1. Setup Data
        $user = User::factory()->create(['role' => 'estimator']);
        $this->actingAs($user);

        $client = Client::factory()->create();
        $product = Product::factory()->create(['unit_price' => 100]);

        $estimate = Estimate::create([
            'title' => 'Original Estimate',
            'estimate_number' => 'EST-001',
            'client_id' => $client->id,
            'estimate_date' => now(),
            'currency' => 'USD',
            'status' => 'draft',
            'type' => 'room_based',
        ]);

        $section = $estimate->sections()->create(['name' => 'Room 1', 'order_index' => 0]);
        $item = $section->items()->create([
            'estimate_id' => $estimate->id, // Add this if your model requires it, though usually section_id suffices via relationship
            'name' => 'Original Item',
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $originalItemId = $item->id;

        // 2. Prepare Update Payload
        // We keep the original item (by passing its ID) and change its quantity.
        // We add a new item.
        $payload = [
            'title' => 'Updated Estimate',
            'client_id' => $client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'status' => 'draft',
            'discount_type' => 'percentage',
            'type' => 'room_based',
            'sections' => [
                [
                    'id' => $section->id,
                    'name' => 'Renamed Room',
                    'items' => [
                        [
                            'id' => $originalItemId, // CRITICAL: Passing ID to ensure update, not recreate
                            'name' => 'Original Item Updated',
                            'quantity' => 2,
                            'unit_price' => 100,
                            'unit_type' => 'nos',
                        ],
                        [
                            'name' => 'New Item',
                            'quantity' => 1,
                            'unit_price' => 50,
                            'unit_type' => 'nos',
                        ],
                    ],
                ],
            ],
        ];

        // 3. Act
        $response = $this->put(route('estimates.update', $estimate), $payload);

        // 4. Assert
        $response->assertRedirect();

        // Check Estimate Updated
        $this->assertEquals('Updated Estimate', $estimate->fresh()->title);

        // Check Section Updated (same ID)
        $this->assertEquals('Renamed Room', $estimate->sections->first()->name);

        // CRITICAL CHECK: Item ID should persist
        $updatedItem = $estimate->items()->where('id', $originalItemId)->first();
        $this->assertNotNull($updatedItem, 'Original item was deleted!');
        $this->assertEquals('Original Item Updated', $updatedItem->name);
        $this->assertEquals(2, $updatedItem->quantity); // 100 * 2 = 200

        // Check New Item Created
        $this->assertEquals(2, $estimate->items->count());
    }
}
