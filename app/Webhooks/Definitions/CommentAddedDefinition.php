<?php

namespace App\Webhooks\Definitions;

class CommentAddedDefinition implements WebhookEventDefinitionInterface
{
    use \App\Webhooks\Traits\ShortenUrls;
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
        $estimate = $resource->estimate;

        $expiration = $estimate->expiry_date ? $estimate->expiry_date->endOfDay() : now()->addDays(30);
        $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('portal.download', $expiration, ['estimate' => $estimate->id]);

        // Shorten URLs for cleaner webhook payloads
        $expiryDays = $expiration->diffInDays(now());
        $shortPdfUrl = $this->shortenUrl($pdfUrl, $expiryDays);
        $shortEstimateUrl = $this->shortenUrl($estimate->public_url, $expiryDays);

        return [
            'id' => $resource->id,
            'content' => $resource->content,
            'total' => $estimate->grand_total,
            'mobile_number' => $estimate->client?->phone,
            'created_at' => $resource->created_at ? $resource->created_at->toIso8601String() : now()->toIso8601String(),
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
            'commenter' => $resource->user ? [
                'name' => $resource->user->name,
                'email' => $resource->user->email,
                'phone' => $resource->user->mobile_number,
            ] : null,
        ];
    }

    public function samplePayload(): array
    {
        return [
            'id' => 101,
            'content' => 'Can we get a discount on the installation?',
            'total' => 1500.00,
            'mobile_number' => '8688771397',
            'created_at' => now()->toIso8601String(),
            'estimate' => [
                'id' => 123,
                'reference' => 'EST-2024-001',
                'total' => 1500.00,
                'url' => 'https://example.com/portal/estimates/123?signature=...',
                'pdf' => 'https://example.com/portal/estimates/123/download?signature=...',
                'created_by' => [
                    'name' => 'Agent Smith',
                    'email' => 'wapmedia3@gmail.com',
                ],
            ],
            'client' => [
                'name' => 'John Doe',
                'email' => 'wapmedia3@gmail.com',
                'phone' => '8688771397',
            ],
            'commenter' => [
                'name' => 'John Doe',
                'email' => 'wapmedia3@gmail.com',
                'phone' => '8688771397',
            ],
        ];
    }
}
