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
        // 1. Log Raw Payload Immediately
        $event = WebhookInboundEvent::create([
            'provider' => $provider,
            'provider_event_id' => $this->extractEventId($request, $provider),
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
            'status' => 'pending',
        ]);

        // 2. Verification (Can be moved to Middleware for cleaner code, but kept here for initial logic)
        if (!$this->verifySignature($request, $provider)) {
            $event->update(['status' => 'failed', 'error' => 'Invalid signature']);
            Log::warning("Webhook: Invalid signature for provider {$provider}");
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // 3. Dispatch for Async Processing
        ProcessInboundWebhook::dispatch($event);

        // 4. Acknowledge Receipt
        return response()->json(['message' => 'Accepted'], 202);
    }

    protected function extractEventId(Request $request, string $provider): ?string
    {
        return match ($provider) {
            'perfex' => $request->input('proposal_id') ?? $request->input('id'),
            default => $request->header('X-Event-Id'),
        };
    }

    protected function verifySignature(Request $request, string $provider): bool
    {
        $secret = config("services.{$provider}.webhook_secret");
        if (!$secret)
            return true; // Fail open for now or strictly? Let's stay safe.

        $signature = $request->header('X-Webhook-Signature') ?? $request->header('X-Perfex-Token');

        // Simple comparison for Perfex (as seen in existing code)
        if ($provider === 'perfex') {
            return $signature === $secret;
        }

        // HMAC comparison for others
        $computed = hash_hmac('sha256', $request->getContent(), $secret);
        return hash_equals($computed, $signature ?? '');
    }
}
