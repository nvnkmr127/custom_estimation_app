<?php

namespace App\Http\Controllers;

use App\Models\PluginModule;
use App\Services\PluginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PluginController extends Controller
{
    protected $pluginService;

    public function __construct(PluginService $pluginService)
    {
        $this->pluginService = $pluginService;
    }

    /**
     * Public inbound webhook endpoint.
     * Accessible at /plugins/catch/{uuid} without user authentication sessions.
     */
    public function catch(Request $request, string $uuid)
    {
        // Find active inbound module
        $module = PluginModule::query()
            ->where('uuid', $uuid)
            ->where('is_active', true)
            ->where('type', 'inbound')
            ->whereHas('plugin', function ($q) {
                $q->where('is_active', true);
            })
            ->first();

        if (!$module) {
            Log::warning("PluginController: Active inbound plugin module not found for UUID: {$uuid}");
            return response()->json(['error' => 'Endpoint not found or inactive.'], 404);
        }

        // Optional simple secret token check (if configured in settings)
        $settings = $module->settings ?? [];
        $expectedToken = $settings['secret'] ?? null;
        if ($expectedToken) {
            $providedToken = $request->header('X-Plugin-Token') ?? $request->query('token') ?? $request->header('X-Webhook-Signature');
            if ($providedToken !== $expectedToken) {
                Log::warning("PluginController: Unauthorized inbound request for module {$module->key}");
                return response()->json(['error' => 'Unauthorized signature token.'], 401);
            }
        }

        // Process webhook payload
        $payload = $request->all();
        if (empty($payload)) {
            $payload = json_decode($request->getContent(), true) ?? [];
        }
        $headers = $request->headers->all();

        $result = $this->pluginService->processInboundWebhook($module, $payload, $headers);

        if ($result['status'] === 'failed') {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
        ], 200);
    }
}
