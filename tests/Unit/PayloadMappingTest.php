<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Webhooks\PayloadMapper;
use App\Jobs\WebhookDeliveryJob;
use App\Models\WebhookConfig;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayloadMappingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_transforms_payload_using_simple_mapping()
    {
        $mapper = new PayloadMapper();
        $source = [
            'payload' => [
                'normalized' => [
                    'user' => ['name' => 'John', 'email' => 'john@example.com']
                ]
            ],
            'meta' => ['id' => 123]
        ];

        $mapping = [
            'external_id' => 'meta.id',
            'full_name' => 'payload.normalized.user.name'
        ];

        $result = $mapper->map($source, $mapping);

        $this->assertEquals([
            'external_id' => 123,
            'full_name' => 'John'
        ], $result);
    }

    /** @test */
    public function it_supports_advanced_transformations()
    {
        $mapper = new PayloadMapper();
        $source = [
            'occurred_at' => '2023-01-01 12:00:00',
            'status' => null
        ];

        $mapping = [
            'event_date' => [
                'source' => 'occurred_at',
                'transform' => 'date:Y-m-d'
            ],
            'state' => [
                'source' => 'status',
                'default' => 'unknown'
            ],
            'static_val' => [
                'value' => 'fixed'
            ],
            'nested.output' => 'occurred_at'
        ];

        $result = $mapper->map($source, $mapping);

        $this->assertEquals('2023-01-01', $result['event_date']);
        $this->assertEquals('unknown', $result['state']);
        $this->assertEquals('fixed', $result['static_val']);
        $this->assertEquals('2023-01-01 12:00:00', $result['nested']['output']);
    }

    /** @test */
    public function it_applies_mapping_in_delivery_job()
    {
        Http::fake();

        $config = WebhookConfig::factory()->create([
            'payload_mapping' => [
                'custom.id' => 'event_id'
            ]
        ]);

        $event = WebhookEvent::factory()->create([
            'payload' => [
                'event_id' => 'abc-123',
                'data' => 'ignored'
            ]
        ]);

        $job = new WebhookDeliveryJob($config, $event);
        $job->handle();

        Http::assertSent(function ($request) {
            $body = $request->data();
            return isset($body['custom']['id']) && $body['custom']['id'] === 'abc-123'
                && !isset($body['data']);
        });
    }
}
