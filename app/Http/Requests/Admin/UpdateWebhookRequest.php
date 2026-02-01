<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('webhook_config'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'url' => ['sometimes', 'url', 'max:255'],
            'http_method' => ['sometimes', 'in:POST,PUT,PATCH'],
            'secret' => ['nullable', 'string', 'max:255'],
            'events' => ['sometimes', 'array', 'min:1'],
            'events.*' => ['required', 'string'],
            'headers' => ['nullable', 'array'],
            'status' => ['sometimes', 'in:active,inactive'],
            'concurrency_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'rate_limit' => ['nullable', 'integer', 'min:1', 'max:3600'],
        ];
    }
}
