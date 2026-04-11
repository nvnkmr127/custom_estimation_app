<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmptyRoomCleanupTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_skips_persisting_empty_rooms_on_create()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();

        $response = $this->actingAs($user)->post(route('estimates.store'), [
            'client_id' => $client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'expiry_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => Estimate::EST_STATUS_DRAFT,
            'currency' => 'USD',
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'type' => 'room_based',
            'sections' => [
                [
                    'name' => 'Empty Room',
                    'items' => [],
                ],
                [
                    'name' => 'Kitchen',
                    'items' => [
                        [
                            'name' => 'Tile',
                            'unit_price' => 100,
                            'quantity' => 1,
                            'unit_type' => 'nos',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();

        $estimate = Estimate::latest('id')->first();
        $this->assertNotNull($estimate);

        $this->assertDatabaseMissing('estimate_sections', [
            'estimate_id' => $estimate->id,
            'name' => 'Empty Room',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('estimate_sections', [
            'estimate_id' => $estimate->id,
            'name' => 'Kitchen',
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_deletes_rooms_that_become_empty_on_update()
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

        $section = $estimate->sections()->create([
            'name' => 'Bathroom',
            'order_index' => 0,
            'section_type' => 'room',
        ]);

        $item = EstimateItem::create([
            'estimate_id' => $estimate->id,
            'estimate_section_id' => $section->id,
            'name' => 'Mirror',
            'unit_price' => 50,
            'quantity' => 1,
            'unit_type' => 'nos',
            'order_index' => 0,
        ]);

        $response = $this->actingAs($user)->put(route('estimates.update', $estimate), [
            'title' => $estimate->title,
            'client_id' => $client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'expiry_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => Estimate::EST_STATUS_DRAFT,
            'currency' => 'USD',
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'type' => 'room_based',
            'sections' => [
                [
                    'id' => $section->id,
                    'name' => 'Bathroom',
                    'items' => [],
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertSoftDeleted('estimate_sections', ['id' => $section->id]);
        $this->assertSoftDeleted('estimate_items', ['id' => $item->id]);
    }
}

