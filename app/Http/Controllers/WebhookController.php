<?php

namespace App\Http\Controllers;

use App\Models\WebhookInboundEvent;
use App\Jobs\ProcessInboundWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle inbound webhooks.
     */
    public function handle(Request $request, string $provider)
    {
        // 1. Log Raw Payload (Idempotent Check)
        $eventId = $this->extractEventId($request, $provider);
        $event = WebhookInboundEvent::firstOrCreate(
            ['provider' => $provider, 'provider_event_id' => $eventId],
            [
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
                'status' => 'pending',
            ]
        );

        // If it already existed and was processed, just return success
        if (!$event->wasRecentlyCreated && $event->status === 'processed') {
            return response()->json(['message' => 'Already processed'], 200);
        }

        // 2. Verification
        if (!$this->verifySignature($request, $provider)) {
            $event->update([
                'status' => 'failed',
                'error_message' => 'Invalid signature',
                'error' => 'Invalid signature'
            ]);
            Log::warning("Webhook [{$provider}]: Invalid signature. Content-Type: " . $request->header('Content-Type'));
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // 3. Dispatch for Async Processing
        ProcessInboundWebhook::dispatch($event);

        // 4. Acknowledge Receipt
        return response()->json(['message' => 'Accepted'], 202);
    }

    /**
     * Handle generic inbound webhooks via unique URL.
     */
    public function catch(Request $request, string $uuid)
    {
        $endpoint = \App\Models\WebhookInboundEndpoint::where('uuid', $uuid)->where('is_active', true)->firstOrFail();

        // 1. IP Filtering
        if ($endpoint->ip_whitelist) {
            $allowed = array_map('trim', explode(',', $endpoint->ip_whitelist));
            if (!in_array($request->ip(), $allowed)) {
                Log::warning("Webhook: IP {$request->ip()} denied for endpoint {$endpoint->name}");
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }

        // 2. Signature Verification
        if ($endpoint->secret && $endpoint->signature_header) {
            $signature = $request->header($endpoint->signature_header);
            // Assuming simplified HMAC-SHA256 for now, can be made configurable later
            $computed = hash_hmac('sha256', $request->getContent(), $endpoint->secret);

            // Note: Some providers use "sha256=<hash>", so we might need simple string containment or normalization
            // For now, let's try direct comparison or "sha256=" prefix check
            if (!hash_equals($computed, $signature ?? '') && !hash_equals("sha256={$computed}", $signature ?? '')) {
                Log::warning("Webhook: Invalid signature for endpoint {$endpoint->name}");

                // We still log the attempt as failed
                WebhookInboundEvent::create([
                    'provider' => $uuid,
                    'provider_event_id' => $request->header('X-Request-ID') ?? (string) \Illuminate\Support\Str::uuid(),
                    'payload' => $request->all(),
                    'headers' => $request->headers->all(),
                    'status' => 'failed',
                    'error_message' => 'Invalid Signature',
                ]);

                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        // 3. Log Valid Payload
        $event = WebhookInboundEvent::create([
            'provider' => $uuid, // Use UUID as provider identifier
            'provider_event_id' => $request->header('X-Request-ID') ?? (string) \Illuminate\Support\Str::uuid(),
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
            'status' => 'pending',
        ]);

        // 4. Dispatch for Async Processing
        ProcessInboundWebhook::dispatch($event);

        return response()->json(['message' => 'Received'], 200);
    }

    protected function extractEventId(Request $request, string $provider): ?string
    {
        return match ($provider) {
            'perfex' => (string) ($request->input('proposal_id') ?? $request->input('id') ?? $request->header('X-Event-Id') ?? $request->header('X-Request-Id') ?? \Illuminate\Support\Str::uuid()),
            default => $request->header('X-Event-Id') ?? $request->header('X-Request-Id') ?? (string) \Illuminate\Support\Str::uuid(),
        };
    }

    protected function verifySignature(Request $request, string $provider): bool
    {
        $secret = config("services.{$provider}.webhook_secret");
        if (!$secret) {
            return false;
        }

        $signature = match ($provider) {
            'perfex' => $request->header('X-Perfex-Token'),
            default => $request->header('X-Webhook-Signature'),
        };

        if ($provider === 'perfex') {
            return hash_equals($secret, $signature ?? '');
        }

        $computed = hash_hmac('sha256', $request->getContent(), $secret);
        return hash_equals($computed, $signature ?? '');
    }
}
