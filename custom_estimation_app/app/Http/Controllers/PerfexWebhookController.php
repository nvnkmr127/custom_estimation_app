<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Estimate;

class PerfexWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Security Check
        $token = $request->header('X-Perfex-Token');
        $secret = config('services.perfex.webhook_secret');

        if (empty($secret) || $token !== $secret) {
            Log::warning('Unauthorized Perfex Webhook attempt from IP: ' . $request->ip());
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Log::info('Perfex Webhook:', $request->all());

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
