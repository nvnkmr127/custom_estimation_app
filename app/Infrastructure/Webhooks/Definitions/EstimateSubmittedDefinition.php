<?php

namespace App\Infrastructure\Webhooks\Definitions;

use App\Models\Estimate;

class EstimateSubmittedDefinition implements WebhookEventDefinitionInterface
{
    public function name(): string
    {
        return 'estimate.submitted_for_approval';
    }

    public function description(): string
    {
        return 'Triggered when an estimate is submitted for approval.';
    }

    public function resourceClass(): string
    {
        return Estimate::class;
    }

    public function buildPayload(object $resource): array
    {
        /** @var Estimate $resource */
        $sender = $resource->creator;
        $client = $resource->client;

        return [
            'id' => $resource->id,
            'reference' => $resource->estimate_number,
            'submitted_at' => now()->toIso8601String(),
            'total' => $resource->grand_total,

            // Sender Details
            'sender_id' => $sender?->id,
            'sender_name' => $sender?->name ?? 'N/A',
            'sender_email' => $sender?->email ?? 'N/A',
            'sender_contact' => $sender?->mobile_number ?? 'N/A',

            // Client Details
            'name' => $client?->name ?? 'N/A',
            'contact_number' => $client?->phone ?? 'N/A',
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 123,
            'reference' => 'EST-2024-001',
            'submitted_at' => now()->toIso8601String(),
            'total' => 1500.00,
            'sender_id' => 1,
            'sender_name' => 'Master Estimator',
            'sender_email' => 'estimator@example.com',
            'sender_contact' => '+91 80000 22222',
            'name' => 'John Doe',
            'contact_number' => '+91 98765 43210',
        ];
    }
}
