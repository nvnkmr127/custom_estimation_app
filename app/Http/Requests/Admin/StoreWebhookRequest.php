<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\WebhookConfig::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'http_method' => ['required', 'in:POST,PUT,PATCH'],
            'secret' => ['nullable', 'string', 'max:255'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string'],
            'headers' => ['nullable', 'array'],
            'status' => ['required', 'in:active,inactive'],
            'concurrency_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'rate_limit' => ['nullable', 'integer', 'min:1', 'max:3600'],
        ];
    }
}
