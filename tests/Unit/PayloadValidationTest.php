<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Webhooks\PayloadValidator;
use App\Models\WebhookConfig;
use App\Models\WebhookEvent;
use App\Jobs\WebhookDeliveryJob;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class PayloadValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_validates_payload_successfully()
    {
        $validator = new PayloadValidator();
        $payload = ['foo' => 'bar', 'age' => 25];
        $rules = ['foo' => 'required|string', 'age' => 'numeric|min:18'];

        // Should not throw
        $validator->validate($payload, $rules);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_throws_exception_on_validation_failure()
    {
        $this->expectException(ValidationException::class);

        $validator = new PayloadValidator();
        $payload = ['foo' => 'bar', 'age' => 10]; // Age < 18
        $rules = ['age' => 'numeric|min:18'];

        $validator->validate($payload, $rules);
    }

    /** @test */
    public function job_sends_to_dlq_on_validation_failure()
    {
        Http::fake(); // Should not be called if validation fails early

        $config = WebhookConfig::factory()->create([
            'validation_schema' => [
                'required_field' => 'required'
            ]
        ]);

        $event = WebhookEvent::factory()->create([
            'payload' => ['other_field' => 'value'] // Missing required_field
        ]);

        $job = new WebhookDeliveryJob($config, $event);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Job handles failure and calls fail($e)
        }

        // Assert DLQ entry created
        $this->assertDatabaseHas('webhook_dead_letters', [
            'webhook_id' => $config->id,
            'webhook_event_id' => $event->id,
        ]);

        // Assert DLQ error reason contains validation message or generic failure
        $dlq = \App\Models\WebhookDeadLetter::first();
        // Laravel humanizes 'required_field' to 'required field'
        $this->assertStringContainsString('required', $dlq->error_reason);

        // Ensure NO HTTP request sent
        Http::assertNothingSent();
    }
}
