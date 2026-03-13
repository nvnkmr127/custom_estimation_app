<?php

namespace App\Webhooks\Definitions;

class ApprovalApprovedDefinition implements WebhookEventDefinitionInterface
{
    use \App\Webhooks\Traits\ShortenUrls;
    public function name(): string
    {
        return 'approval.approved';
    }

    public function description(): string
    {
        return 'Triggered when an approval step is successfully approved.';
    }

    public function resourceClass(): string
    {
        return \App\Models\EstimateApproval::class;
    }

    public function buildPayload(object $resource): array
    {
        /** @var \App\Models\EstimateApproval $resource */
        $estimate = $resource->estimate;

        $expiration = $estimate->expiry_date ? $estimate->expiry_date->endOfDay() : now()->addDays(30);
        $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('portal.download', $expiration, ['estimate' => $estimate->id]);

        // Shorten URLs for cleaner webhook payloads
        $shortPdfUrl = $this->shortenUrl($pdfUrl, $expiration);
        $shortEstimateUrl = $this->shortenUrl($estimate->public_url, $expiration);

        return [
            'id' => $resource->id,
            'status' => 'approved',
            'approved_at' => now()->toIso8601String(),
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
            'id' => 1,
            'status' => 'approved',
            'approved_at' => now()->toIso8601String(),
            'estimate' => [
                'id' => 123,
                'reference' => 'EST-2024-001',
                'total' => 5000.00,
                'url' => 'https://example.com/portal/estimates/123?signature=...',
                'pdf' => 'https://example.com/portal/estimates/123/download?signature=...',
                'created_by' => [
                    'name' => 'Sales Rep',
                    'email' => 'wapmedia3@gmail.com',
                ],
            ],
            'client' => [
                'name' => 'John Doe',
                'email' => 'wapmedia3@gmail.com',
            ],
            'approver' => [
                'name' => 'Manager One',
                'email' => 'wapmedia3@gmail.com',
            ],
        ];
    }
}
