<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWebhookRequest;
use App\Http\Requests\Admin\UpdateWebhookRequest;
use App\Models\WebhookConfig;
use Illuminate\Http\JsonResponse;

class WebhookEndpointController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', WebhookConfig::class);
        return view('admin.webhooks.index');
    }

    public function create()
    {
        $this->authorize('create', WebhookConfig::class);
        return view('admin.webhooks.create');
    }

    public function edit(WebhookConfig $webhook)
    {
        $this->authorize('update', $webhook);
        return view('admin.webhooks.edit', compact('webhook'));
    }

    public function update(UpdateWebhookRequest $request, WebhookConfig $webhookConfig): JsonResponse
    {
        $webhookConfig->update($request->validated());

        return response()->json($webhookConfig);
    }

    public function destroy(WebhookConfig $webhookConfig): JsonResponse
    {
        $this->authorize('delete', $webhookConfig);

        $webhookConfig->delete();

        return response()->json(['message' => 'Webhook endpoint disabled'], 200);
    }
}
