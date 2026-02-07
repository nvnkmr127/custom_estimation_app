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
            $this->getApprovalStamp(),
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
        // Robust Expiry Fallback: Default to 30 days from estimate date if NULL
        $estimateDate = $this->estimate->estimate_date ? \Carbon\Carbon::parse($this->estimate->estimate_date) : now();
        $expiryDate = $this->estimate->expiry_date
            ? $this->estimate->expiry_date
            : $estimateDate->copy()->addDays(30);

        return [
            'estimate_number' => $this->estimate->estimate_number,
            'estimate_title' => $this->estimate->title ?? 'Estimate',
            'estimate_date' => $estimateDate->format('M d, Y'),
            'expiry_date' => $expiryDate->format('M d, Y'),
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
            'company_logo' => $this->processLogo($this->settings['company_logo'] ?? $this->settings['app_logo'] ?? ''),
            'copyright_year' => date('Y'),
        ];
    }

    protected function getNotesAndTerms(): array
    {
        return [
            'client_note' => nl2br(htmlspecialchars($this->estimate->client_note ?? '')),
            'terms' => $this->estimate->final_terms_html, // Using HTML version for smart merge and formatting
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
            'has_terms' => $this->estimate->has_final_terms ? 1 : 0,
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

    protected function getApprovalStamp(): array
    {
        if ($this->estimate->status !== 'accepted') {
            return ['approval_stamp' => '', 'signature_image' => ''];
        }

        $date = $this->estimate->signed_at ? $this->estimate->signed_at->format('M d, Y') : '';
        $id = str_pad($this->estimate->id, 6, '0', STR_PAD_LEFT);

        // Circular SVG Stamp (Professional Ink Style)
        $svg = '
        <div class="approval-stamp-container" style="display:inline-block; position:relative; width:180px; height:180px;">
            <svg viewBox="0 0 200 200" style="width:100%; height:100%; filter: drop-shadow(0 2px 5px rgba(0,0,0,0.1));">
                <circle cx="100" cy="100" r="92" fill="none" stroke="#10b981" stroke-width="2" stroke-dasharray="4,4" />
                <circle cx="100" cy="100" r="85" fill="none" stroke="#10b981" stroke-width="6" />
                <circle cx="100" cy="100" r="75" fill="none" stroke="#10b981" stroke-width="2" />
                <path id="pdfCurve" d="M 40,100 A 60,60 0 1,1 160,100" fill="none" />
                <text font-family="Arial, sans-serif" font-size="12" font-weight="bold" fill="#059669" letter-spacing="2">
                    <textPath href="#pdfCurve" startOffset="50%" text-anchor="middle">OFFICIALLY APPROVED</textPath>
                </text>
                <rect x="20" y="85" width="160" height="30" rx="4" fill="#10b981" />
                <text x="100" y="106" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" font-weight="900" fill="#ffffff">APPROVED</text>
                <path d="M 40,100 A 60,60 0 1,0 160,100" fill="none" id="pdfCurve2" />
                <text font-family="Arial, sans-serif" font-size="10" font-weight="bold" fill="#059669" letter-spacing="1">
                    <textPath href="#pdfCurve2" startOffset="50%" text-anchor="middle" dominant-baseline="hanging">' . $date . '</textPath>
                </text>
            </svg>
        </div>';

        $signatureHtml = '';
        if ($this->estimate->signature) {
            $signatureHtml = '<div class="digital-signature-box" style="margin-top:10px; border-top:1px solid #e2e8f0; padding-top:10px;">
                <p style="font-size: 10px; color:#64748b; margin:0; text-transform:uppercase;">Digitally Signed By Client</p>
                <img src="' . $this->estimate->signature . '" style="max-height:60px; max-width:200px; display:block;" />
                <p style="font-size: 8px; color:#94a3b8; margin-top:5px;">UID: ' . $id . ' | ' . $this->estimate->signed_at->format('H:i T') . '</p>
            </div>';
        }

        return [
            'approval_stamp' => $svg,
            'signature_image' => $signatureHtml
        ];
    }

    protected function processLogo($logoPath)
    {
        // 1. Try provided path
        if (!empty($logoPath)) {
            $path = public_path($logoPath);
            if (file_exists($path)) {
                return '<img src="file://' . $path . '" class="company-logo" style="max-height: 80px;" />';
            }
        }

        // 2. Fallback to default common logo paths if generic setting is missing
        reset:
        $defaults = ['/logo.png', '/images/logo.png', '/storage/logo.png'];
        foreach ($defaults as $d) {
            $path = public_path($d);
            if (file_exists($path)) {
                return '<img src="file://' . $path . '" class="company-logo" style="max-height: 80px;" />';
            }
        }

        // 3. Last Resort: Placeholder with Company Name if no image found
        $name = $this->settings['company_legal_name'] ?? config('app.name');
        return '<div style="font-size: 24px; font-weight: 900; color: #1e293b; letter-spacing: -1px;">' . htmlspecialchars($name) . '</div>';
    }
}
