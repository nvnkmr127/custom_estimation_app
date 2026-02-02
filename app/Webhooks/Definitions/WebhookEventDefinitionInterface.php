<?php

namespace App\Webhooks\Definitions;

interface WebhookEventDefinitionInterface
{
    /**
     * Get the unique event type identifier (e.g. 'estimate.created').
     */
    public function name(): string;

    /**
     * Get a human-readable description.
     */
    public function description(): string;

    /**
     * Get the resource class associated with this event (e.g. Estimate::class).
     */
    public function resourceClass(): string;

    /**
     * Build the normalized payload for this event.
     */
    public function buildPayload(object $resource): array;

    /**
     * Get a sample payload for documentation/UI.
     */
    public function samplePayload(): array;
}
