<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PerfexWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Perfex Webhook attempt from IP: ' . $request->ip(), [
            'headers' => $request->headers->all(),
            'all_data' => $request->all(),
            'raw_content' => $request->getContent(),
        ]);

        // Security Check
        $token = $request->header('X-Perfex-Token') ?? $request->input('token');
        $secret = config('services.perfex.webhook_secret');

        if (empty($secret)) {
            Log::warning('Perfex Webhook: Secret is not configured in .env (PERFEX_WEBHOOK_SECRET)');
            return response()->json(['error' => 'Secret not configured'], 500);
        }

        if ($token !== $secret) {
            Log::warning('Unauthorized Perfex Webhook attempt. Token mismatch.', [
                'received' => $token,
                'is_empty' => empty($token)
            ]);

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $type = $request->input('event_type') ?? $request->input('action');
        $data = $request->all();

        // 1. Proposal Accepted
        if ($type === 'proposal_accepted') {
            $id = $data['proposal_id'] ?? $data['id'] ?? null;
            if ($id) {
                Estimate::where('perfex_proposal_id', $id)->update(['status' => 'accepted']);

                return response()->json(['status' => 'updated']);
            }
        }

        // 2. Proposal Declined
        if ($type === 'proposal_declined') {
            $id = $data['proposal_id'] ?? $data['id'] ?? null;
            if ($id) {
                Estimate::where('perfex_proposal_id', $id)->update(['status' => 'declined']);

                return response()->json(['status' => 'updated']);
            }
        }

        return response()->json(['status' => 'received']);
    }
}
