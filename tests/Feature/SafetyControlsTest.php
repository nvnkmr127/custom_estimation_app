<?php

namespace Tests\Feature;

use App\Core\Events\DomainEvent;
use App\Models\Automation;
use App\Models\AutomationTrigger;
use App\Models\AutomationStep;
use App\Models\AutomationCondition;
use App\Models\AutomationAction;
use App\Models\AutomationExecutionLog;
use App\Models\EventLog;
use App\Services\Automation\AutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SafetyControlsTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AutomationService::class);
    }

    public function test_and_logic_evaluation()
    {
        $estimate = \App\Models\Estimate::factory()->create(['status' => 'pending']);

        $automation = Automation::create([
            'name' => 'AND Rule',
            'condition_logic' => 'AND',
            'is_active' => true
        ]);

        AutomationTrigger::create(['automation_id' => $automation->id, 'event_name' => 'test.event']);

        AutomationCondition::create([
            'automation_id' => $automation->id,
            'type' => 'payload',
            'field' => 'price',
            'operator' => '>',
            'value' => 100
        ]);
        AutomationCondition::create([
            'automation_id' => $automation->id,
            'type' => 'payload',
            'field' => 'status',
            'operator' => '=',
            'value' => 'active'
        ]);

        $step = AutomationStep::create(['automation_id' => $automation->id, 'order' => 0]);
        AutomationAction::create([
            'automation_step_id' => $step->id,
            'type' => 'status_update',
            'config' => ['field' => 'status', 'value' => 'processed']
        ]);

        $event = $this->createMockEvent(['price' => 150, 'status' => 'active'], 'Estimate', $estimate->id);
        $this->service->evaluate($event);
        $this->assertDatabaseHas('automation_execution_logs', ['automation_id' => $automation->id, 'status' => 'success']);

        $estimate->refresh();
        $this->assertEquals('processed', $estimate->status);

        AutomationExecutionLog::truncate();

        $eventFailure = $this->createMockEvent(['price' => 50, 'status' => 'active'], 'Estimate', $estimate->id);
        $this->service->evaluate($eventFailure);
        $this->assertDatabaseMissing('automation_execution_logs', ['automation_id' => $automation->id]);
    }

    public function test_or_logic_evaluation()
    {
        $estimate = \App\Models\Estimate::factory()->create();

        $automation = Automation::create([
            'name' => 'OR Rule',
            'condition_logic' => 'OR',
            'is_active' => true
        ]);

        AutomationTrigger::create(['automation_id' => $automation->id, 'event_name' => 'test.event']);

        AutomationCondition::create([
            'automation_id' => $automation->id,
            'type' => 'payload',
            'field' => 'price',
            'operator' => '>',
            'value' => 100
        ]);
        AutomationCondition::create([
            'automation_id' => $automation->id,
            'type' => 'payload',
            'field' => 'status',
            'operator' => '=',
            'value' => 'active'
        ]);

        $step = AutomationStep::create(['automation_id' => $automation->id, 'order' => 0]);
        AutomationAction::create([
            'automation_step_id' => $step->id,
            'type' => 'status_update',
            'config' => ['field' => 'status', 'value' => 'processed']
        ]);

        // One matches
        $event = $this->createMockEvent(['price' => 50, 'status' => 'active'], 'Estimate', $estimate->id);
        $this->service->evaluate($event);
        $this->assertDatabaseHas('automation_execution_logs', ['automation_id' => $automation->id, 'status' => 'success']);

        AutomationExecutionLog::truncate();

        // None matches
        $eventFailure = $this->createMockEvent(['price' => 50, 'status' => 'inactive'], 'Estimate', $estimate->id);
        $this->service->evaluate($eventFailure);
        $this->assertDatabaseMissing('automation_execution_logs', ['automation_id' => $automation->id]);
    }

    public function test_rate_limiting()
    {
        $estimate = \App\Models\Estimate::factory()->create();

        $automation = Automation::create([
            'name' => 'Rate Limited Rule',
            'settings' => [
                'is_enabled' => true,
                'rate_limit_count' => 2,
                'rate_limit_period' => 60
            ],
            'is_active' => true
        ]);

        AutomationTrigger::create(['automation_id' => $automation->id, 'event_name' => 'test.event']);

        $step = AutomationStep::create(['automation_id' => $automation->id, 'order' => 0]);
        AutomationAction::create([
            'automation_step_id' => $step->id,
            'type' => 'status_update',
            'config' => ['field' => 'status', 'value' => 'processed']
        ]);

        $event = $this->createMockEvent([], 'Estimate', $estimate->id);

        // First execution
        $this->service->evaluate($event);
        $this->assertDatabaseHas('automation_execution_logs', ['automation_id' => $automation->id, 'status' => 'success']);
        $this->assertEquals(1, AutomationExecutionLog::count());

        // Second execution
        $this->service->evaluate($event);
        $this->assertEquals(2, AutomationExecutionLog::count());

        // Third execution (should be limited)
        $this->service->evaluate($event);
        $this->assertEquals(2, AutomationExecutionLog::count());
    }

    public function test_max_executions_per_entity()
    {
        $estimate = \App\Models\Estimate::factory()->create();

        $automation = Automation::create([
            'name' => 'Max Per Entity Rule',
            'settings' => [
                'is_enabled' => true,
                'max_executions_per_entity' => 1
            ],
            'is_active' => true
        ]);

        AutomationTrigger::create(['automation_id' => $automation->id, 'event_name' => 'test.event']);

        $step = AutomationStep::create(['automation_id' => $automation->id, 'order' => 0]);
        AutomationAction::create([
            'automation_step_id' => $step->id,
            'type' => 'status_update',
            'config' => ['field' => 'status', 'value' => 'processed']
        ]);

        // Create an EventLog record so we can associate AutomationExecutionLog with it
        $eventId = 'evt_123';
        EventLog::create([
            'event_id' => $eventId,
            'event_name' => 'test.event',
            'entity_type' => 'Estimate',
            'entity_id' => $estimate->id,
            'event_class' => 'TestEvent',
            'source' => 'test',
            'payload' => []
        ]);

        $event = $this->createMockEvent([], 'Estimate', $estimate->id, $eventId);

        // First execution
        $this->service->evaluate($event);
        $this->assertEquals(1, AutomationExecutionLog::where('status', 'success')->count());

        // Second execution for SAME entity (should be skipped)
        $this->service->evaluate($event);
        $this->assertEquals(1, AutomationExecutionLog::where('status', 'success')->count());

        // Execution for DIFFERENT entity
        $estimate2 = \App\Models\Estimate::factory()->create();
        $eventId2 = 'evt_456';
        EventLog::create([
            'event_id' => $eventId2,
            'event_name' => 'test.event',
            'entity_type' => 'Estimate',
            'entity_id' => $estimate2->id,
            'event_class' => 'TestEvent',
            'source' => 'test',
            'payload' => []
        ]);
        $event2 = $this->createMockEvent([], 'Estimate', $estimate2->id, $eventId2);
        $this->service->evaluate($event2);
        $this->assertEquals(2, AutomationExecutionLog::where('status', 'success')->count());
    }

    protected function createMockEvent(array $payload, $entityType = 'System', $entityId = null, $eventId = null)
    {
        return new class ($payload, $entityType ?? 'System', $entityId, $eventId) implements DomainEvent {
            protected $payload;
            protected $entityType;
            protected $entityId;
            protected $eventId;

            public function __construct($payload, $entityType, $entityId, $eventId)
            {
                $this->payload = $payload;
                $this->entityType = $entityType;
                $this->entityId = $entityId;
                $this->eventId = $eventId ?? 'evt_' . uniqid();
            }
            public function getEventName(): string
            {
                return 'test.event';
            }
            public function getPayload(): array
            {
                return $this->payload;
            }
            public function getEntityType(): string
            {
                return $this->entityType;
            }
            public function getEntityId(): int|string|null
            {
                return $this->entityId;
            }
            public function getEventId(): string
            {
                return $this->eventId;
            }
            public function getTriggeredBy(): ?int
            {
                return null;
            }
            public function getOccurredOn(): \DateTimeImmutable
            {
                return new \DateTimeImmutable();
            }
            public function getSource(): string
            {
                return 'test';
            }
        };
    }
}
