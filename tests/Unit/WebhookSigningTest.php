<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\WebhookDeliveryJob;
use App\Webhooks\WebhookSignatureVerifier;
use App\Models\WebhookConfig;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookSigningTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_valid_signature_and_timestamp()
    {
        Http::fake();

        $secret = 'test-secret-key';

        $config = WebhookConfig::factory()->create([
            'url' => 'https://example.com',
            'secret' => $secret,
            'http_method' => 'POST'
        ]);

        $event = WebhookEvent::factory()->create([
            'payload' => ['data' => 'test'],
        ]);

        $job = new WebhookDeliveryJob($config, $event);
        $job->handle(); // Will process synchronously

        Http::assertSent(function ($request) use ($secret) {
            $body = $request->body();
            $timestamp = $request->header('X-Webhook-Timestamp')[0];
            $signature = $request->header('X-Webhook-Signature')[0];

            $verifier = new WebhookSignatureVerifier();
            return $verifier->isValid($body, $signature, $timestamp, $secret);
        });
    }

    /** @test */
    public function it_rejects_replay_attacks_old_timestamp()
    {
        $verifier = new WebhookSignatureVerifier();
        $secret = 'secret';
        $payload = '{"foo":"bar"}';
        $oldTimestamp = time() - 600; // 10 mins ago

        $signature = hash_hmac('sha256', "{$oldTimestamp}.{$payload}", $secret);

        $this->assertFalse(
            $verifier->isValid($payload, $signature, (string) $oldTimestamp, $secret)
        );
    }

    /** @test */
    public function it_rejects_tampered_payload()
    {
        $verifier = new WebhookSignatureVerifier();
        $secret = 'secret';
        $payload = '{"foo":"bar"}';
        $timestamp = time();

        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        // Verify with tampered payload
        $this->assertFalse(
            $verifier->isValid('{"foo":"baz"}', $signature, (string) $timestamp, $secret)
        );
    }
}
