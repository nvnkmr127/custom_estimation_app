<?php

namespace Tests\Feature;

use App\Core\Events\BaseEvent;
use App\Models\Automation;
use App\Models\AutomationTrigger;
use App\Models\AutomationStep;
use App\Models\AutomationCondition;
use App\Models\AutomationAction;
use App\Services\Mail\EmailDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Mockery;

class TestAutomationEvent extends BaseEvent
{
    public function getEventName(): string
    {
        return 'test.automation';
    }
    public function getPayload(): array
    {
        return ['key' => 'value'];
    }
    public function getEntityType(): string
    {
        return 'test';
    }
    public function getEntityId(): int|string|null
    {
        return 1;
    }
}

class AutomationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_automation_is_triggered_by_event()
    {
        // 1. Arrange
        $dispatcher = Mockery::mock(EmailDispatcher::class);
        $this->app->instance(EmailDispatcher::class, $dispatcher);

        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with('test@example.com', 'Automation Success', 'emails.generic_automation', Mockery::any());

        $automation = Automation::create([
            'name' => 'Test Email Rule',
            'is_active' => true,
        ]);

        AutomationTrigger::create(['automation_id' => $automation->id, 'event_name' => 'test.automation']);

        $step = AutomationStep::create(['automation_id' => $automation->id, 'order' => 0]);
        AutomationAction::create([
            'automation_step_id' => $step->id,
            'type' => 'email',
            'config' => [
                'to' => 'test@example.com',
                'subject' => 'Automation Success',
            ]
        ]);

        // 2. Act
        $event = new TestAutomationEvent();
        event($event);

        // 3. Assert - Mockery will assert 'once' during close
        $this->assertTrue(true);
    }

    public function test_automation_filters_by_conditions()
    {
        // 1. Arrange
        $dispatcher = Mockery::mock(EmailDispatcher::class);
        $this->app->instance(EmailDispatcher::class, $dispatcher);

        $dispatcher->shouldNotReceive('dispatch');

        $automation = Automation::create([
            'name' => 'Conditional Rule',
            'is_active' => true,
        ]);

        AutomationTrigger::create(['automation_id' => $automation->id, 'event_name' => 'test.automation']);

        AutomationCondition::create([
            'automation_id' => $automation->id,
            'type' => 'payload',
            'field' => 'key',
            'operator' => '=',
            'value' => 'wrong'
        ]);

        $step = AutomationStep::create(['automation_id' => $automation->id, 'order' => 0]);
        AutomationAction::create([
            'automation_step_id' => $step->id,
            'type' => 'email',
            'config' => ['to' => 'fail@example.com']
        ]);

        // 2. Act
        $event = new TestAutomationEvent();
        event($event);

        // 3. Assert
        $this->assertTrue(true);
    }

    public function test_webhook_action()
    {
        // 1. Arrange
        Http::fake();

        $automation = Automation::create([
            'name' => 'Webhook Rule',
            'is_active' => true,
        ]);

        AutomationTrigger::create(['automation_id' => $automation->id, 'event_name' => 'test.automation']);

        $step = AutomationStep::create(['automation_id' => $automation->id, 'order' => 0]);
        AutomationAction::create([
            'automation_step_id' => $step->id,
            'type' => 'webhook',
            'config' => ['url' => 'https://example.com/webhook']
        ]);

        // 2. Act
        $event = new TestAutomationEvent();
        event($event);

        // 3. Assert
        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook' &&
                $request['event'] === 'test.automation';
        });
    }
}
