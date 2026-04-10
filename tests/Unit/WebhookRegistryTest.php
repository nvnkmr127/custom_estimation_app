<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Webhooks\WebhookEventRegistry;
use App\Webhooks\Definitions\EstimateCreatedDefinition;

class WebhookRegistryTest extends TestCase
{
    /** @test */
    public function it_registers_default_definitions_via_provider()
    {
        $registry = app(WebhookEventRegistry::class);

        $this->assertArrayHasKey('estimate.created', $registry->all());
        $this->assertArrayHasKey('estimate.updated', $registry->all());
        $this->assertArrayHasKey('client.created', $registry->all());
    }

    /** @test */
    public function it_can_retrieve_definition_by_name()
    {
        $registry = app(WebhookEventRegistry::class);
        $def = $registry->get('estimate.created');

        $this->assertInstanceOf(EstimateCreatedDefinition::class, $def);
        $this->assertEquals('estimate.created', $def->name());
    }

    /** @test */
    public function it_returns_null_for_unknown_definition()
    {
        $registry = app(WebhookEventRegistry::class);
        $this->assertNull($registry->get('unknown.event'));
    }

    /** @test */
    public function it_provides_grouped_events_for_ui()
    {
        $registry = app(WebhookEventRegistry::class);
        $groups = $registry->getGroupedEvents();

        $this->assertArrayHasKey('Estimates Management', $groups);
        $this->assertArrayHasKey('Client Relations', $groups);

        $estimateEvents = collect($groups['Estimates Management'])->pluck('name');
        $this->assertTrue($estimateEvents->contains('estimate.created'));
    }
}
