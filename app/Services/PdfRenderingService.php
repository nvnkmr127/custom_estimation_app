<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\PdfTemplate;

class PdfRenderingService
{
    protected $estimate;

    protected $settings;

    protected $variables = [];

    public function render(PdfTemplate $template, Estimate $estimate)
    {
        $this->estimate = $estimate;
        $this->settings = \App\Models\Setting::pluck('value', 'key');

        // Prepare variables once
        $this->variables = $this->prepareVariables();

        $html = $template->html_content;

        // 1. Logic Blocks (Conditionals & Loops)
        $html = $this->parseConditionals($html);
        $html = $this->parseSections($html); // Handle Sections First
        $html = $this->parseLoops($html);    // Handle remaining global loops

        // 2. Variable Replacement
        $html = $this->replaceVariables($html);

        // 3. Process Images (Convert relative URLs to absolute paths for DOMPDF)
        $html = $this->processImages($html);

        // 4. Inject CSS
        $cssVars = ":root { --primary-color: {$template->primary_color}; --secondary-color: {$template->secondary_color}; --font-body: {$template->font_family}; } body { font-family: var(--font-body) !important; } .page-break-before { page-break-before: always; } .page-break-after { page-break-after: always; } .avoid-break { page-break-inside: avoid; }";

        $finalCss = $cssVars . ($template->css_content ?? '');

        if ($finalCss) {
            // If HTML has <head>, inject there. Otherwise prepend.
            if (strpos($html, '</head>') !== false) {
                $html = str_replace('</head>', '<style>' . $finalCss . '</style></head>', $html);
            } else {
                $html = '<style>' . $finalCss . '</style>' . $html;
            }
        }

        // 5. Inject Watermark
        if (!empty($template->watermark_text)) {
            $opacity = $template->watermark_opacity ?? 0.1;
            $text = htmlspecialchars($template->watermark_text);

            $watermarkCss = "position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 80px; font-weight: bold; color: {$template->primary_color}; opacity: {$opacity}; pointer-events: none; z-index: 9999; white-space: nowrap;";
            $watermarkHtml = "<div style=\"{$watermarkCss}\">{$text}</div>";

            // Inject after body start
            if (strpos($html, '<body') !== false) {
                $html = preg_replace('/<body[^>]*>/', '$0' . $watermarkHtml, $html, 1);
            } else {
                $html .= $watermarkHtml;
            }
        }

        return $html;
    }

    protected function processImages($html)
    {
        // Convert src="/..." to absolute file paths
        return preg_replace_callback('/src=["\']([^"\']+)["\']/i', function ($matches) {
            $src = $matches[1];

            // If it's already http/https, leave it (though DOMPDF might need enable_remote)
            if (strpos($src, 'http') === 0) {
                return $matches[0];
            }

            // If it starts with /, map to public path
            if (strpos($src, '/') === 0) {
                $path = public_path($src);
                if (file_exists($path)) {
                    return 'src="file://' . $path . '"';
                }
            }

            return $matches[0];
        }, $html);
    }

    protected function prepareVariables()
    {
        return [
            // Estimate Details
            'estimate_number' => $this->estimate->estimate_number,
            'estimate_title' => $this->estimate->title ?? 'Estimate',
            'estimate_date' => \Carbon\Carbon::parse($this->estimate->estimate_date)->format('M d, Y'),
            'expiry_date' => $this->estimate->expiry_date ? \Carbon\Carbon::parse($this->estimate->expiry_date)->format('M d, Y') : 'N/A',
            'status' => ucfirst($this->estimate->status),
            'currency' => $this->estimate->currency,

            // Values
            'subtotal' => number_format($this->estimate->subtotal, 2),
            'discount_total' => number_format($this->estimate->discount_total, 2),
            'tax_total' => number_format($this->estimate->total_tax, 2),
            'grand_total' => number_format($this->estimate->grand_total, 2),

            // Client Info
            'client_id' => $this->estimate->client_id,
            'client_name' => $this->estimate->client ? $this->estimate->client->name : 'N/A',
            'client_email' => $this->estimate->client ? $this->estimate->client->email : '',
            'client_address' => $this->estimate->client ? $this->estimate->client->address : '',

            // Company Info
            'company_name' => $this->settings['company_legal_name'] ?? config('app.name'),
            'company_email' => $this->settings['company_email'] ?? '',
            'company_phone' => $this->settings['company_phone'] ?? '',
            'company_address' => $this->settings['company_address_street'] ?? '',
            'company_city' => $this->settings['company_address_city'] ?? '',

            // Notes
            'client_note' => nl2br($this->estimate->client_note ?? ''),
            'terms' => nl2br($this->estimate->terms ?? ''),
            'admin_note' => nl2br($this->estimate->admin_note ?? ''),

            // Raw numeric values for Logic (since formatted strings are always true-ish)
            '_raw_subtotal' => $this->estimate->subtotal,
            '_raw_discount_total' => $this->estimate->discount_total,
            '_raw_tax_total' => $this->estimate->total_tax,
            '_raw_grand_total' => $this->estimate->grand_total,
        ];
    }

    protected function replaceVariables($html)
    {
        foreach ($this->variables as $key => $value) {
            // Logic keys starting with _raw are internal, don't expose them unless requested specifically
            if (strpos($key, '_raw_') === 0) {
                continue;
            }

            $html = str_replace('{' . $key . '}', $value, $html);
        }

        return $html;
    }

    protected function parseConditionals($html)
    {
        // 1. Specific IF_NOT logic matches first to ensure they aren't consumed by generic IF (which matches "IF_NOT_...")
        // {IF_NOT_variable}...{END_IF}
        $html = preg_replace_callback('/\{IF_NOT_([a-zA-Z0-9_]+)\}(.*?)\{END_IF\}/s', function ($matches) {
            $key = $matches[1];
            $content = $matches[2];

            $checkKey = '_raw_' . $key;
            $val = isset($this->variables[$checkKey]) ? $this->variables[$checkKey] : ($this->variables[$key] ?? null);

            if (isset($this->variables['_raw_' . $key])) {
                $val = $this->variables['_raw_' . $key];
                $isTrue = $val > 0;
            } else {
                $isTrue = !empty($val) && $val != 0;
            }

            return !$isTrue ? $content : '';
        }, $html);

        // 2. Generic IF logic: {IF_variable}...{END_IF}
        $html = preg_replace_callback('/\{IF_([a-zA-Z0-9_]+)\}(.*?)\{END_IF\}/s', function ($matches) {
            $key = $matches[1];
            $content = $matches[2];

            // For convenience, map common keys to their raw counterparts if valid
            $checkKey = '_raw_' . $key;
            $val = isset($this->variables[$checkKey]) ? $this->variables[$checkKey] : ($this->variables[$key] ?? null);

            // Determine truthiness
            $isTrue = !empty($val) && $val != 0;

            if (isset($this->variables['_raw_' . $key])) {
                $val = $this->variables['_raw_' . $key];
                $isTrue = $val > 0;
            }

            return $isTrue ? $content : '';
        }, $html);

        return $html;
    }

    protected function parseSections($html)
    {
        if ($this->estimate->type !== 'room_based') {
            return preg_replace('/\{LOOP_SECTIONS\}.*?\{END_LOOP\}/s', '', $html);
        }

        return preg_replace_callback('/\{LOOP_SECTIONS\}(.*?)\{END_LOOP\}/s', function ($matches) {
            $sectionBlock = $matches[1];
            $renderedSections = '';

            foreach ($this->estimate->sections as $section) {
                $sectionHtml = $sectionBlock;
                $sectionHtml = str_replace('{section_name}', $section->name, $sectionHtml);
                $sectionHtml = str_replace('{section_subtotal}', number_format($section->subtotal ?? 0, 2), $sectionHtml);

                // Calculate percentage for charts
                $grandTotal = $this->estimate->grand_total > 0 ? $this->estimate->grand_total : 1;
                $percent = ($section->subtotal / $grandTotal) * 100;
                $sectionHtml = str_replace('{section_percent}', round($percent, 1), $sectionHtml);

                $sectionHtml = preg_replace_callback('/\{LOOP_ITEMS\}(.*?)\{END_LOOP\}/s', function ($itemMatches) use ($section) {
                    $itemBlock = $itemMatches[1];

                    return $this->renderItems($itemBlock, $section->items);
                }, $sectionHtml);

                $renderedSections .= $sectionHtml;
            }

            return $renderedSections;
        }, $html);
    }

    protected function parseLoops($html)
    {
        return preg_replace_callback('/\{LOOP_ITEMS\}(.*?)\{END_LOOP\}/s', function ($matches) {
            $block = $matches[1];
            $items = $this->estimate->type === 'room_based'
                ? $this->estimate->sections->flatMap->items
                : $this->estimate->items;

            return $this->renderItems($block, $items);
        }, $html);
    }

    protected function renderItems($block, $items)
    {
        $renderedItems = '';
        foreach ($items as $item) {
            $itemHtml = $block;
            $itemVars = [
                '{item_name}' => $item->name,
                '{item_description}' => $item->description ?? '',
                '{item_quantity}' => $item->quantity + 0,
                '{item_unit}' => $item->unit_price,
                '{item_price}' => number_format($item->unit_price, 2),
                '{item_subtotal}' => number_format($item->unit_price * $item->quantity, 2),
                '{item_tax_percent}' => $item->tax_1 + $item->tax_2,
                '{item_total}' => number_format($item->total, 2),
            ];

            foreach ($itemVars as $key => $value) {
                $itemHtml = str_replace($key, $value, $itemHtml);
            }
            $renderedItems .= $itemHtml;
        }

        return $renderedItems;
    }

    /**
     * Render HTML and cache the PDF output.
     * Returns the absolute path to the cached PDF.
     */
    public function renderAndCache(PdfTemplate $template, Estimate $estimate)
    {
        $cacheKey = $this->resolveCacheKey($estimate, $template);
        $cachePath = storage_path('app/public/estimates_cache/' . $cacheKey);

        // Check if cached file exists and is fresher than the estimate/template update
        // Logic: The key already contains timestamps, so if file exists with that name, it's valid.
        if (file_exists($cachePath)) {
            return $cachePath;
        }

        // Clean up old cache files for this estimate to save space?
        // (For simplicity in this MVP, we might skip auto-cleanup or implement a cron job later)
        // Let's implement basic "delete any other pdf for this estimate" logic if we wanted.

        $html = $this->render($template, $estimate);

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper($template->paper_size ?? 'a4', $template->orientation ?? 'portrait');

            if ($template->is_password_protected && $estimate->client && $estimate->client->email) {
                $pdf->setEncryption($estimate->client->email);
            }

            $pdf->save($cachePath);

            return $cachePath;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("PDF Generation Failed for Estimate #{$estimate->id}: " . $e->getMessage());

            // Fallback content in case of severe failure
            return null;
        }
    }

    protected function resolveCacheKey(Estimate $estimate, PdfTemplate $template)
    {
        // Key depends on Estimate updated_at and Template updated_at.
        // Any change invalidates the key.
        $eTs = $estimate->updated_at->timestamp;
        $tTs = $template->updated_at->timestamp;

        return "estimate_{$estimate->id}_v{$eTs}_tpl{$template->id}_v{$tTs}.pdf";
    }
}
