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

    public function show(WebhookConfig $webhook)
    {
        $this->authorize('view', $webhook);

        return view('admin.webhooks.show', compact('webhook'));
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

    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $webhook = WebhookConfig::create($request->validated());

        return response()->json($webhook, 201);
    }

    public function update(UpdateWebhookRequest $request, WebhookConfig $webhook): JsonResponse
    {
        $webhook->update($request->validated());

        return response()->json($webhook);
    }

    public function destroy(WebhookConfig $webhook): JsonResponse
    {
        $this->authorize('delete', $webhook);

        $webhook->delete();

        return response()->json(['message' => 'Webhook endpoint disabled'], 200);
    }
}
