<?php

namespace App\Webhooks\Definitions;

use App\Models\EstimateApproval;

class ApprovalChangeRequestedDefinition implements WebhookEventDefinitionInterface
{
    use \App\Webhooks\Traits\ShortenUrls;
    public function name(): string
    {
        return 'approval.change_requested';
    }

    public function description(): string
    {
        return 'Triggered when an approver requests changes to an estimate.';
    }

    public function resourceClass(): string
    {
        return EstimateApproval::class;
    }

    public function buildPayload(object $resource): array
    {
        /** @var EstimateApproval $resource */
        $estimate = $resource->estimate;

        $expiration = $estimate->expiry_date ? $estimate->expiry_date->endOfDay() : now()->addDays(30);
        $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('portal.download', $expiration, ['estimate' => $estimate->id]);

        // Shorten URLs for cleaner webhook payloads
        $shortPdfUrl = $this->shortenUrl($pdfUrl, $expiration);
        $shortEstimateUrl = $this->shortenUrl($estimate->public_url, $expiration);

        return [
            'id' => $resource->id,
            'status' => 'change_requested',
            'comments' => $resource->comments,
            'requested_at' => $resource->updated_at->toIso8601String(),
            'estimate' => [
                'id' => $estimate->id,
                'reference' => $estimate->estimate_number,
                'total' => $estimate->grand_total,
                'url' => $shortEstimateUrl,
                'pdf' => $shortPdfUrl,
                'created_by' => $estimate->creator ? [
                    'name' => $estimate->creator->name,
                    'email' => $estimate->creator->email,
                    'phone' => $estimate->creator->mobile_number,
                ] : null,
            ],
            'client' => $estimate->client ? [
                'name' => $estimate->client->name,
                'email' => $estimate->client->email,
                'phone' => $estimate->client->phone,
                'secondary_phone' => $estimate->client->secondary_phone,
            ] : null,
            'approver' => $resource->user ? [
                'name' => $resource->user->name,
                'email' => $resource->user->email,
                'phone' => $resource->user->mobile_number,
            ] : null,
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 46,
            'status' => 'change_requested',
            'comments' => 'Please adjust the quantity of Item A.',
            'requested_at' => now()->toIso8601String(),
            'estimate' => [
                'id' => 124,
                'reference' => 'EST-2024-002',
                'total' => 3000.00,
                'url' => 'https://example.com/portal/estimates/124?signature=...',
                'pdf' => 'https://example.com/portal/estimates/124/download?signature=...',
                'created_by' => [
                    'name' => 'Alice Estimator',
                    'email' => 'wapmedia3@gmail.com',
                ],
            ],
            'client' => [
                'name' => 'Small Biz Inc',
                'email' => 'wapmedia3@gmail.com',
            ],
            'approver' => [
                'name' => 'Sarah Manager',
                'email' => 'wapmedia3@gmail.com',
            ],
        ];
    }
}
