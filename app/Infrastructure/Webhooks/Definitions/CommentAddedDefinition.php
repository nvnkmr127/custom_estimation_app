<?php

namespace App\Infrastructure\Webhooks\Definitions;

class CommentAddedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'comment.added';
    }

    public function description(): string
    {
        return 'Triggered when a comment is added to an estimate.';
    }

    public function resourceClass(): string
    {
        return \App\Models\EstimateComment::class;
    }

    public function buildPayload(object $resource): array
    {
        /** @var \App\Models\EstimateComment $resource */
        return [
            'id' => $resource->id,
            'estimate_id' => $resource->estimate_id,
            'estimate_number' => $resource->estimate->estimate_number,
            'user' => [
                'id' => $resource->user_id,
                'name' => $resource->user?->name,
                'email' => $resource->user?->email,
                'mobile_number' => $resource->user?->mobile_number,
            ],
            'content' => $resource->content,
            'created_at' => $resource->created_at ? $resource->created_at->toIso8601String() : now()->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 101,
            'estimate_id' => 123,
            'estimate_number' => 'EST-2024-001',
            'user' => [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'mobile_number' => '8688771397',
            ],
            'content' => 'Can we get a discount on the installation?',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
