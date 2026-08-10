<?php

namespace Tests\Unit;

use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\EstimateSection;
use App\Services\EstimateDiffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateDiffServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_diff_with_overview_and_items()
    {
        $oldEstimate = Estimate::factory()->create([
            'version' => 1,
            'grand_total' => 1000.00,
            'subtotal' => 1000.00,
        ]);

        $section = EstimateSection::create([
            'estimate_id' => $oldEstimate->id,
            'name' => 'Living Room',
        ]);

        $item1 = EstimateItem::create([
            'estimate_id' => $oldEstimate->id,
            'estimate_section_id' => $section->id,
            'name' => 'Sofa',
            'quantity' => 1,
            'unit_price' => 500.00,
            'total' => 500.00,
        ]);

        $item2 = EstimateItem::create([
            'estimate_id' => $oldEstimate->id,
            'estimate_section_id' => $section->id,
            'name' => 'Table',
            'quantity' => 1,
            'unit_price' => 500.00,
            'total' => 500.00,
        ]);

        // Version 2
        $newEstimate = Estimate::factory()->create([
            'version' => 2,
            'parent_id' => $oldEstimate->id,
            'grand_total' => 1300.00,
            'subtotal' => 1300.00,
        ]);

        $newSection = EstimateSection::create([
            'estimate_id' => $newEstimate->id,
            'name' => 'Living Room',
        ]);

        // Modified Item 1 (Sofa unit price changed to 600)
        EstimateItem::create([
            'estimate_id' => $newEstimate->id,
            'estimate_section_id' => $newSection->id,
            'name' => 'Sofa',
            'quantity' => 1,
            'unit_price' => 600.00,
            'total' => 600.00,
            'original_item_id' => $item1->id,
        ]);

        // Item 2 removed (Table not included)

        // Item 3 Added (Lamp added for 700)
        EstimateItem::create([
            'estimate_id' => $newEstimate->id,
            'estimate_section_id' => $newSection->id,
            'name' => 'Lamp',
            'quantity' => 1,
            'unit_price' => 700.00,
            'total' => 700.00,
        ]);

        $diffService = new EstimateDiffService();
        $diff = $diffService->calculateDiff($oldEstimate, $newEstimate);

        $this->assertEquals(1, $diff['summary']['old_version']);
        $this->assertEquals(2, $diff['summary']['new_version']);
        $this->assertEquals(1000.00, $diff['summary']['old_grand_total']);
        $this->assertEquals(1300.00, $diff['summary']['new_grand_total']);
        $this->assertEquals(300.00, $diff['summary']['net_change']);
        $this->assertEquals(30.0, $diff['summary']['percent_change']);
        $this->assertEquals(1, $diff['summary']['added_count']);
        $this->assertEquals(1, $diff['summary']['modified_count']);
        $this->assertEquals(1, $diff['summary']['removed_count']);
    }
}
