<?php

namespace Tests\Audit;

use Tests\TestCase;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\User;
use App\Models\Client;
use App\Services\EstimateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class VersioningSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EstimateService::class);
        $this->user = User::factory()->create(['role' => 'admin']); // Ensure admin/creator rights
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_creates_initial_estimate_correctly()
    {
        $client = Client::factory()->create();

        $data = [
            'client_id' => $client->id,
            'title' => 'Test Estimate',
            'estimate_date' => now(),
            'expiry_date' => now()->addDays(7),
        ];

        $items = [
            [
                'name' => 'Item 1',
                'quantity' => 1,
                'unit_price' => 100,
                'order_index' => 0
            ]
        ];

        $estimate = $this->service->createEstimate($data, $items, 'standard');

        $this->assertEquals(1, $estimate->version);
        $this->assertTrue($estimate->is_current_version);
        $this->assertNull($estimate->parent_id);
    }

    /** @test */
    public function it_handles_manual_version_creation()
    {
        // Setup Initial
        $client = Client::factory()->create();
        $estimate = $this->service->createEstimate([
            'client_id' => $client->id,
            'estimate_date' => now()
        ], [], 'standard');

        // Create Version
        $v2 = $this->service->createVersion($estimate);

        // Assertions
        $this->assertEquals(2, $v2->version);
        $this->assertTrue($v2->is_current_version, 'New manual version should be current');

        $estimate->refresh();
        $this->assertFalse($estimate->is_current_version, 'Old version should not be current');

        $this->assertEquals($estimate->id, $v2->parent_id, 'Parent ID should link to original');
    }

    /** @test */
    public function it_enforces_branching_on_approved_estimate_edit()
    {
        $client = Client::factory()->create();
        $estimate = $this->service->createEstimate([
            'client_id' => $client->id,
            'estimate_date' => now()
        ], [['name' => 'Original', 'quantity' => 1, 'unit_price' => 100]], 'standard');

        // Approve it
        $estimate->update(['status' => Estimate::STATUS_APPROVED]);

        // Attempt Update
        $newData = $estimate->toArray();
        $newData['title'] = 'Updated Title';

        // This should trigger branching
        $updatedEstimate = $this->service->updateEstimate($estimate, $newData, [['name' => 'Original', 'quantity' => 1, 'unit_price' => 100]], 'standard');

        // Assertions
        $this->assertNotEquals($updatedEstimate->id, $estimate->id, 'Should have created a new estimate');
        $this->assertNotEquals($updatedEstimate->estimate_number, $estimate->estimate_number);
        $this->assertTrue(str_contains($updatedEstimate->estimate_number, '-v2'), 'Should have v2 suffix');

        // Verify Content
        $this->assertEquals('Updated Title', $updatedEstimate->title);

        // Verify "Proposal" status (is_current_version = true as per PRD visibility requirement)
        $this->assertTrue($updatedEstimate->is_current_version, 'Proposal draft SHOULD be current (visible) immediately');
    }

    /** @test */
    public function it_deep_clones_items_and_sections()
    {
        $client = Client::factory()->create();

        // Complex Setup
        $estimate = $this->service->createEstimate([
            'client_id' => $client->id,
            'estimate_date' => now()
        ], [], 'room_based'); // Room based to test sections

        $section = $estimate->sections()->create(['name' => 'Living Room', 'order_index' => 0]);
        $item = $estimate->items()->create([
            'estimate_section_id' => $section->id,
            'name' => 'Sofa',
            'quantity' => 1,
            'unit_price' => 500,
            'tax_1' => 10,
            'options' => ['color' => 'red'], // JSON field
            'height' => 100
        ]);

        // Branch
        $v2 = $this->service->createVersion($estimate);

        // Verification
        $v2 = $v2->fresh(['sections.items']);
        $this->assertCount(1, $v2->sections);
        $v2Section = $v2->sections->first();
        $this->assertEquals('Living Room', $v2Section->name);
        $this->assertNotEquals($section->id, $v2Section->id, 'Section should be a new record');

        $this->assertCount(1, $v2Section->items);
        $v2Item = $v2Section->items->first();

        $this->assertEquals('Sofa', $v2Item->name);
        $this->assertEquals(500, $v2Item->unit_price);
        $this->assertEquals(['color' => 'red'], $v2Item->options); // Check JSON clone
        $this->assertEquals(100, $v2Item->height);
        $this->assertNotEquals($item->id, $v2Item->id, 'Item should be a new record');
    }

    /** @test */
    public function it_handles_concurrency_in_version_numbering()
    {
        // This is hard to test perfectly in SQLite memory, but we can verify logic.
        // We will simulate the state where multiple versions exist.

        $client = Client::factory()->create();
        $root = $this->service->createEstimate(['client_id' => $client->id, 'estimate_date' => now()], [], 'standard');

        // Create v2 manually
        $v2 = $this->service->createVersion($root);

        // Manually insert v3 to simulate race condition or gap
        $v3 = $v2->replicate();
        $v3->version = 3;
        $v3->estimate_number = $root->estimate_number . '-v3';
        $v3->save();

        // Now try to create another version from Root. It should detect v3 exists and make v4.
        // Logic: max(version) from family.

        $v4 = $this->service->createVersion($root);

        $this->assertEquals(4, $v4->version, 'Should skip over existing versions correctly');
        $this->assertEquals($root->estimate_number . '-v4', $v4->estimate_number);
    }
}
