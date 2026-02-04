<?php

namespace App\Services\Pdf;

use App\Models\Estimate;
use App\Models\EstimateItem;
use Illuminate\Support\Collection;

class EstimateViewModel
{
    protected $estimate;
    protected $settings;

    public function __construct(Estimate $estimate, array $settings = [])
    {
        $this->estimate = $estimate;
        $this->settings = $settings;
    }

    public function toArray(): array
    {
        return array_merge(
            $this->getEstimateDetails(),
            $this->getCreatorInfo(),
            $this->getFinancials(),
            $this->getClientInfo(),
            $this->getCompanyInfo(),
            $this->getNotesAndTerms(),
            $this->getFlags(),
            $this->getArrays()
        );
    }

    protected function getCreatorInfo(): array
    {
        return [
            'estimator_name' => $this->estimate->creator->name ?? 'N/A',
            'estimator_email' => $this->estimate->creator->email ?? 'N/A',
        ];
    }

    protected function getEstimateDetails(): array
    {
        return [
            'estimate_number' => $this->estimate->estimate_number,
            'estimate_title' => $this->estimate->title ?? 'Estimate',
            'estimate_date' => $this->estimate->estimate_date ? \Carbon\Carbon::parse($this->estimate->estimate_date)->format('M d, Y') : '',
            'expiry_date' => $this->estimate->expiry_date ? \Carbon\Carbon::parse($this->estimate->expiry_date)->format('M d, Y') : 'N/A',
            'status' => ucfirst($this->estimate->status),
            'currency' => $this->estimate->currency ?? '$',
        ];
    }

    protected function getFinancials(): array
    {
        return [
            'subtotal' => number_format((float) $this->estimate->subtotal, 2),
            'discount_total' => number_format((float) $this->estimate->discount_total, 2),
            'tax_total' => number_format((float) $this->estimate->total_tax, 2),
            'grand_total' => number_format((float) $this->estimate->grand_total, 2),
            'transportation_charges' => number_format((float) ($this->estimate->transportation_charges ?? 0), 2),

            // Raw numeric values for calculations if needed
            '_raw_subtotal' => $this->estimate->subtotal,
            '_raw_discount_total' => $this->estimate->discount_total,
            '_raw_tax_total' => $this->estimate->total_tax,
            '_raw_grand_total' => $this->estimate->grand_total,
        ];
    }

    protected function getClientInfo(): array
    {
        $client = $this->estimate->client;
        return [
            'client_id' => $this->estimate->client_id,
            'client_name' => $client ? $client->name : 'N/A',
            'client_email' => $client ? $client->email : '',
            'client_phone' => $client ? $client->phone : '',
            'client_address' => $client ? $client->address : '',
        ];
    }

    protected function getCompanyInfo(): array
    {
        return [
            'company_name' => $this->settings['company_legal_name'] ?? config('app.name'),
            'company_email' => $this->settings['company_email'] ?? '',
            'company_phone' => $this->settings['company_phone'] ?? '',
            'company_address' => $this->settings['company_address_street'] ?? '',
            'company_city' => $this->settings['company_address_city'] ?? '',
            'company_logo' => $this->processLogo($this->settings['company_logo'] ?? ''),
        ];
    }

    protected function getNotesAndTerms(): array
    {
        return [
            'client_note' => nl2br(htmlspecialchars($this->estimate->client_note ?? '')),
            'terms' => nl2br(htmlspecialchars($this->estimate->terms ?? '')),
            'admin_note' => nl2br(htmlspecialchars($this->estimate->admin_note ?? '')),
        ];
    }

    protected function getFlags(): array
    {
        return [
            'has_discount' => $this->estimate->has_discount ? 1 : 0,
            'has_tax' => $this->estimate->has_tax ? 1 : 0,
            'has_transportation' => $this->estimate->has_transportation ? 1 : 0,
            'has_client_note' => $this->estimate->has_client_note ? 1 : 0,
            'has_terms' => $this->estimate->has_terms ? 1 : 0,
            'has_items' => $this->estimate->items->count() > 0 ? 1 : 0,
            'room_based' => $this->estimate->type === 'room_based' ? 1 : 0,
        ];
    }

    protected function getArrays(): array
    {
        // This is where "items" or "sections" data would be prepared
        // However, the current service seems to handle loops separately via parseLoops/parseSections directly on the model.
        // Ideally, we should expose 'items' as an array of ItemViewModels here, but the existing parser regex logic iterates over the model relationship.
        // For now, let's keep the flat scalar variables here.
        return [];
    }

    protected function processLogo($logoPath)
    {
        if (empty($logoPath))
            return '';

        $path = public_path($logoPath);
        if (file_exists($path)) {
            return '<img src="file://' . $path . '" class="company-logo" style="max-height: 80px;" />';
        }
        return '';
    }
}
