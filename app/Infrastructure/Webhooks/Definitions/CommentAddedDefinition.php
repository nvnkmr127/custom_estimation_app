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
            'user_id' => $resource->user_id,
            'content' => $resource->content,
            'created_at' => $resource->created_at ? $resource->created_at->toIso8601String() : now()->toIso8601String(),
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 101,
            'estimate_id' => 123,
            'user_id' => 1,
            'content' => 'Can we get a discount on the installation?',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
