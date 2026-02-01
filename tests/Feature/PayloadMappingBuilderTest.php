<?php

namespace Tests\Feature;

use App\Livewire\Admin\Webhooks\Components\PayloadMapperBuilder;
use App\Livewire\Admin\Webhooks\Form;
use App\Models\WebhookConfig;
use Livewire\Livewire;
use Tests\TestCase;

class PayloadMappingBuilderTest extends TestCase
{
    /** @test */
    public function it_can_add_and_remove_mapping_rows()
    {
        Livewire::test(PayloadMapperBuilder::class)
            ->assertCount('mapping', 0)
            ->call('addRow')
            ->assertCount('mapping', 1)
            ->call('addRow')
            ->assertCount('mapping', 2)
            ->call('removeRow', 0)
            ->assertCount('mapping', 1);
    }

    /** @test */
    public function it_generates_preview()
    {
        $sample = ['user' => ['id' => 1, 'name' => 'John']];
        $mapping = [
            ['target' => 'customer_id', 'source' => 'user.id', 'default' => '', 'transform' => '', 'is_advanced' => false]
        ];

        Livewire::test(PayloadMapperBuilder::class, ['mapping' => $mapping, 'samplePayload' => $sample])
            ->assertSet('preview.customer_id', 1);
    }

    /** @test */
    public function it_emits_updated_mapping()
    {
        $sample = ['user' => ['id' => 1]];

        Livewire::test(PayloadMapperBuilder::class, ['mapping' => [], 'samplePayload' => $sample])
            ->call('addRow')
            ->set('mapping.0.target', 'customer_id')
            ->set('mapping.0.source', 'user.id')
            // Assert event emitted
            ->assertSet('preview.error', null) // Ensure no mapping error
            ->assertDispatched('mapping-updated'); // Check existence only first
    }

    /** @test */
    public function it_handles_transformations()
    {
        $sample = ['created_at' => '2023-01-01 12:00:00'];
        $mapping = [
            [
                'target' => 'date',
                'source' => 'created_at',
                'default' => '',
                'transform' => 'date:Y-m-d',
                'is_advanced' => false
            ]
        ];

        Livewire::test(PayloadMapperBuilder::class, ['mapping' => $mapping, 'samplePayload' => $sample])
            ->assertSet('preview.date', '2023-01-01');
    }
}
