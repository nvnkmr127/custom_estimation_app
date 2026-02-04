<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\PdfTemplate;

class PdfRenderingService
{
    protected $estimate;

    protected $settings;

    protected $variables = [];
    protected $context;
    protected $isWeb = false;

    public function render(PdfTemplate $template, Estimate $estimate, $isWeb = false)
    {
        $this->estimate = $estimate;
        $this->isWeb = $isWeb;
        // Ensure comments are loaded for PDF generation to avoid N+1
        $this->estimate->loadMissing(['items.comments.user', 'sections.items.comments.user']);

        $this->settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        // Initialize Context
        $variables = $this->prepareVariables($template->html_content);
        $this->context = new \App\Services\Pdf\TemplateContext($variables);

        $html = $template->html_content;

        // 1. Handle Loops First
        // This expands the content and evaluates loop-specific conditionals (item_is_package, has_width, etc.)
        $html = $this->parseSections($html);
        $html = $this->parseLoops($html);

        // 2. Handle Conditionals Last
        // This handles global flags that wrap loops (IF_room_based, has_discount, etc.)
        $html = $this->parseConditionals($html);

        // 2. Variable Replacement
        $html = $this->replaceVariables($html);

        // 3. Process Images (Convert relative URLs to absolute paths for DOMPDF)
        $html = $this->processImages($html, $isWeb);

        // 4. Inject CSS
        // Attempt to load local font first, fallback to Google Fonts
        $localFont = $this->getLocalFontPath($template->font_family);
        $fontCss = "";

        if ($localFont && !$isWeb) {
            $fontCss = "@font-face { font-family: '{$template->font_family}'; src: url('file://{$localFont}'); font-weight: normal; font-style: normal; }";
        } else {
            $fontCss = "@import url('https://fonts.googleapis.com/css2?family=" . urlencode($template->font_family) . ":wght@400;700&display=swap');";
        }

        $cssVars = $fontCss . " :root { --primary-color: {$template->primary_color}; --secondary-color: {$template->secondary_color}; --font-body: {$template->font_family}; } body { font-family: '{$template->font_family}', sans-serif !important; width: 100%; overflow-x: hidden; } table { width: 100%; border-collapse: collapse; } tr { page-break-inside: avoid; } .page-break-before { page-break-before: always; } .page-break-after { page-break-after: always; } .avoid-break { page-break-inside: avoid; }";

        // Add default styles for comments (aligned with Web View Slate-500 #64748b and Green-500 #22c55e)
        $cssVars .= " .item-comments { margin-top: 5px; font-size: 0.9em; color: #64748b; } .comment-row { margin-bottom: 2px; } .clarified-status { color: #22c55e; font-weight: bold; } .item-image { max-width: 50px; max-height: 50px; object-fit: contain; }";

        if ($this->isWeb) {
            $cssVars .= " 
                @media (max-width: 768px) {
                    table { display: block; overflow-x: auto; -webkit-overflow-scrolling: touch; }
                    .watermark-text { font-size: 4rem !important; }
                }
                body { background-color: transparent !important; }
            ";
        }

        $finalCss = $cssVars . ($template->css_content ?? '');

        // Pre-process CSS variables for DOMPDF (which has buggy var() support)
        $finalCss = str_replace([
            'var(--primary-color)',
            'var(--secondary-color)',
            'var(--font-body)'
        ], [
            $template->primary_color,
            $template->secondary_color,
            "'" . $template->font_family . "'"
        ], $finalCss);

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
            $watermarkCss = "
            .watermark-container {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                z-index: -1000;
                pointer-events: none;
                width: 100%;
                text-align: center;
            }
            .watermark-text {
                font-size: 80pt;
                font-weight: bold;
                color: #000;
                opacity: {$opacity};
                text-transform: uppercase;
                white-space: nowrap;
            }";

            // Append CSS
            $html = str_replace('</style>', $watermarkCss . '</style>', $html);

            // Append HTML Body
            $watermarkHtml = '<div class="watermark-container"><div class="watermark-text">' . htmlspecialchars($template->watermark_text) . '</div></div>';

            if (strpos($html, '<body') !== false) {
                // Insert after body tag
                $html = preg_replace('/(<body[^>]*>)/i', '$1' . $watermarkHtml, $html);
            } else {
                // Prepend if no body tag
                $html = $watermarkHtml . $html;
            }
        }

        return $html;
    }

    protected function processImages($html, $isWeb = false)
    {
        // Convert src="/..." to absolute file paths
        return preg_replace_callback('/src=["\']([^"\']+)["\']/i', function ($matches) use ($isWeb) {
            $src = $matches[1];

            // If it's already http/https, leave it (though DOMPDF might need enable_remote)
            if (strpos($src, 'http') === 0) {
                return $matches[0];
            }

            // If it starts with /, map to public path
            if (strpos($src, '/') === 0) {
                if ($isWeb) {
                    return $matches[0];
                }
                $path = public_path($src);
                if (file_exists($path)) {
                    return 'src="file://' . $path . '"';
                }
            }

            return $matches[0];
        }, $html);
    }

    protected function getLocalFontPath($fontName)
    {
        $formatted = strtolower(str_replace(' ', '_', $fontName));
        $path = public_path("fonts/{$formatted}.ttf");
        return file_exists($path) ? $path : null;
    }

    protected function prepareVariables($htmlContent = '')
    {
        // Initialize View Model
        $viewModel = new \App\Services\Pdf\EstimateViewModel($this->estimate, $this->settings);
        $vars = $viewModel->toArray();

        // Add Dynamic Totals View
        $vars['dynamic_totals'] = view('partials.estimates.pdf-totals', ['estimate' => $this->estimate])->render();

        // Add Raw Flags for compatibility w/ existing regex logic (though ViewModel provides them too)
        $vars['room_based'] = $this->estimate->type === 'room_based' ? 1 : 0;
        $vars['_raw_room_based'] = $this->estimate->type === 'room_based' ? 1 : 0; // Legacy support for internal check

        // Lazy Load Charts only if requested in template
        if (strpos($htmlContent, '{CHART_ROOMS}') !== false) {
            $vars['CHART_ROOMS'] = ($this->estimate->type === 'room_based' && $this->estimate->sections->count() > 0)
                ? $this->generateRoomChartUrl()
                : 'https://placehold.co/1x1?text=';
        }

        // New Dynamic Charts
        $charts = [
            '{CHART_SECTIONS_PIE}' => ['pie', 'sections'],
            '{CHART_SECTIONS_DOUGHNUT}' => ['doughnut', 'sections'],
            '{CHART_SECTIONS_BAR}' => ['bar', 'sections'],
            '{CHART_ITEMS_PIE}' => ['pie', 'items'],
            '{CHART_ITEMS_DOUGHNUT}' => ['doughnut', 'items'],
            '{CHART_ITEMS_BAR}' => ['bar', 'items'],
        ];

        foreach ($charts as $tag => $config) {
            if (strpos($htmlContent, $tag) !== false) {
                $vars[str_replace(['{', '}'], '', $tag)] = $this->generateQuickChart($config[0], $config[1]);
            }
        }

        return $vars;
    }

    protected function generateRoomChartUrl()
    {
        return $this->generateQuickChart('doughnut', 'sections');
    }

    protected function generateQuickChart($type, $source)
    {
        // Use QuickChart.io
        $labels = [];
        $data = [];
        $backgroundColor = [];

        if ($source === 'sections' && $this->estimate->sections->count() > 0) {
            foreach ($this->estimate->sections as $section) {
                $labels[] = $section->name;
                $data[] = $section->subtotal;
            }
        } elseif ($source === 'items') {
            // Top 5 items by total
            $items = $this->estimate->items->sortByDesc('total')->take(5);
            if ($this->estimate->type === 'room_based') {
                $items = $this->estimate->sections->flatMap->items->sortByDesc('total')->take(5);
            }

            foreach ($items as $item) {
                $labels[] = \Illuminate\Support\Str::limit($item->name, 15);
                $data[] = $item->total;
            }
        }

        // Default colors
        $colors = ['#2563eb', '#1e40af', '#64748b', '#94a3b8', '#cbd5e1', '#ef4444', '#f59e0b', '#10b981'];
        $backgroundColor = array_slice($colors, 0, count($data));

        $chartConfig = [
            'type' => $type, // pie, doughnut, bar
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'data' => $data,
                        'backgroundColor' => $backgroundColor,
                        'label' => 'Cost'
                    ]
                ]
            ],
            'options' => [
                'plugins' => [
                    'legend' => [
                        'position' => 'right',
                        'display' => $type !== 'bar' // Hide legend for bar charts usually
                    ],
                    'title' => [
                        'display' => true,
                        'text' => ucfirst($source) . ' Breakdown'
                    ]
                ]
            ]
        ];

        return 'https://quickchart.io/chart?w=500&h=300&c=' . urlencode(json_encode($chartConfig));
    }

    protected function replaceVariables($html)
    {
        $vars = $this->context ? $this->context->all() : $this->variables;

        foreach ($vars as $key => $value) {
            // Logic keys starting with _raw are internal, don't expose them unless requested specifically
            if (strpos($key, '_raw_') === 0) {
                continue;
            }

            // Convert null to empty string, and handle numeric/string values
            $displayValue = (string) ($value ?? '');

            // Support both {key}, { key }, and encoded %7Bkey%7D (common in attributes after sanitizer)
            $html = str_replace(['{' . $key . '}', '{ ' . $key . ' }', '%7B' . $key . '%7D', '%7B%20' . $key . '%20%7D'], $displayValue, $html);
        }

        return $html;
    }

    protected function parseConditionals($html, $extraVars = [])
    {
        $scopeVars = $this->context ? $this->context->merge($extraVars)->all() : array_merge($this->variables, $extraVars);

        // We use a loop to handle nested conditionals from the inside out
        // However, regex-based nesting is tricky. A better approach for this simplified parser 
        // is to repeatedly evaluate the "innermost" tags.

        $iterations = 0;
        $maxIterations = 5; // Support up to 5 levels of nesting

        while ($iterations < $maxIterations) {
            $originalHtml = $html;

            // 1. Specific IF_NOT logic (Innermost - looking for the nearest ENDIF)
            $html = preg_replace_callback('/\{(?:IF_NOT_|%7BIF_NOT_)([a-zA-Z0-9_]+)(?:\s*\}|%7D)((?:(?!\{(?:IF|IF_NOT|ENDIF|END_IF)).)*?)\{(?:\s*ENDIF\s*|\s*END_IF\s*|%7BENDIF%7D|%7BEND_IF%7D)\}/si', function ($matches) use ($scopeVars) {
                $key = $matches[1];
                $content = $matches[2];

                if (!array_key_exists($key, $scopeVars) && !array_key_exists('_raw_' . $key, $scopeVars)) {
                    return $matches[0]; // Unknown key in this scope, skip
                }

                $checkKey = '_raw_' . $key;
                $val = isset($scopeVars[$checkKey]) ? $scopeVars[$checkKey] : ($scopeVars[$key] ?? null);
                $isTrue = !empty($val) && $val != 0;

                return !$isTrue ? $content : '';
            }, $html);

            // 2. Generic IF logic (Innermost)
            $html = preg_replace_callback('/\{(?:IF_|%7BIF_)([a-zA-Z0-9_]+)(?:\s*(==|!=|>=|<=|>|<)\s*([a-zA-Z0-9_]+))?(?:\s*\}|%7D)((?:(?!\{(?:IF|IF_NOT|ENDIF|END_IF)).)*?)\{(?:\s*ENDIF\s*|\s*END_IF\s*|%7BENDIF%7D|%7BEND_IF%7D)\}/si', function ($matches) use ($scopeVars) {
                $key = $matches[1];
                $operator = $matches[2] ?? null;
                $compareValue = $matches[3] ?? null;
                $content = $matches[4];

                if (!array_key_exists($key, $scopeVars) && !array_key_exists('_raw_' . $key, $scopeVars)) {
                    return $matches[0];
                }

                $checkKey = '_raw_' . $key;
                $actualValue = isset($scopeVars[$checkKey]) ? $scopeVars[$checkKey] : ($scopeVars[$key] ?? null);

                if (!$operator) {
                    $isTrue = !empty($actualValue) && $actualValue != 0;
                    if (isset($scopeVars['_raw_' . $key])) {
                        $isTrue = (float) $scopeVars['_raw_' . $key] > 0;
                    }
                } else {
                    if ($operator === '==')
                        $isTrue = (string) $actualValue == (string) $compareValue;
                    elseif ($operator === '!=')
                        $isTrue = (string) $actualValue != (string) $compareValue;
                    else
                        $isTrue = false;
                }

                return $isTrue ? $content : '';
            }, $html);

            if ($html === $originalHtml)
                break;
            $iterations++;
        }

        return $html;
    }

    protected function parseSections($html)
    {
        // Support encoded LOOP_SECTIONS tag and whitespace
        $pattern = '/\{(?:\s*LOOP_SECTIONS\s*|%7BLOOP_SECTIONS%7D)\}(.*?)\{(?:\s*END_LOOP_SECTIONS\s*|%7BEND_LOOP_SECTIONS%7D)\}/si';

        if ($this->estimate->type !== 'room_based') {
            return preg_replace($pattern, '', $html);
        }

        return preg_replace_callback($pattern, function ($matches) {
            $sectionBlock = $matches[1];
            $renderedSections = '';

            foreach ($this->estimate->sections as $section) {
                // Prepare Section Local Variables for conditionals
                $sectionVars = [
                    'section_is_package' => $section->is_package ? 1 : 0,
                    '_raw_section_is_package' => $section->is_package ? 1 : 0,
                ];

                $sectionHtml = $sectionBlock;

                // Process Section Conditionals (before replacements)
                $sectionHtml = $this->parseConditionals($sectionHtml, $sectionVars);

                $sectionHtml = str_replace(['{section_name}', '{ section_name }'], $section->name, $sectionHtml);
                // Use standardized Model accessor for consistency
                $formattedTotal = number_format($section->total, 2);
                $sectionHtml = str_replace(['{section_subtotal}', '{ section_subtotal }'], $formattedTotal, $sectionHtml);
                $sectionHtml = str_replace(['{section_total}', '{ section_total }'], $formattedTotal, $sectionHtml);

                $sectionHtml = preg_replace_callback('/\{(?:\s*LOOP_ITEMS\s*|%7BLOOP_ITEMS%7D)\}(.*?)\{(?:\s*END_LOOP\s*|%7BEND_LOOP%7D)\}/si', function ($itemMatches) use ($section) {
                    $itemBlock = $itemMatches[1];

                    return $this->renderItems($itemBlock, $section->items, $section);
                }, $sectionHtml);

                $renderedSections .= $sectionHtml;
            }

            return $renderedSections;
        }, $html);
    }
    protected function parseLoops($html)
    {
        return preg_replace_callback('/\{(?:\s*LOOP_ITEMS\s*|%7BLOOP_ITEMS%7D)\}(.*?)\{(?:\s*END_LOOP\s*|%7BEND_LOOP%7D)\}/si', function ($matches) {
            $block = $matches[1];

            // If we are room-based, this global loop should probably be empty or only show loose items
            // However, the Elite template uses it for the non-room-based fallback.
            if ($this->estimate->type === 'room_based') {
                return ''; // Let parseSections handle it
            }

            $items = $this->estimate->items;
            return $this->renderItems($block, $items);
        }, $html);
    }

    protected function renderItems($block, $items, $parentSection = null)
    {
        $renderedItems = '';
        foreach ($items as $item) {

            // Format Comments
            $commentsHtml = '';
            if ($item->comments->isNotEmpty()) {
                $commentsHtml = '<div class="item-comments">';
                foreach ($item->comments as $comment) {
                    // Only show comments relevant to client (or all if desired? Defaulting to all for now as 'client' usually sees the PDF)
                    $statusLabel = $comment->status === 'clarified' ? '<span class="clarified-status">[Clarified]</span> ' : '';
                    $author = $comment->isClientComment() ? ($comment->client_name ?: 'Client') : ($comment->user->name ?? 'Staff');

                    $commentsHtml .= '<div class="comment-row">'
                        . $statusLabel
                        . '<strong>' . htmlspecialchars($author) . ':</strong> '
                        . nl2br(htmlspecialchars($comment->comment))
                        . '</div>';
                }
                $commentsHtml .= '</div>';
            }

            // Process Item Image
            $itemImageHtml = '';
            $product = $item->product;
            if ($product && $product->images && $product->images->count() > 0) {
                $image = $product->images->first();
                $imagePath = $image->path ?? $image->url ?? null;

                if ($imagePath) {
                    if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                        $itemImageHtml = '<img src="' . $imagePath . '" class="item-image" />';
                    } else {
                        $absPath = public_path($imagePath);
                        if (file_exists($absPath)) {
                            $src = $this->isWeb ? $imagePath : 'file://' . $absPath;
                            $itemImageHtml = '<img src="' . $src . '" class="item-image" />';
                        }
                    }
                }
            }

            $itemHtml = $block;

            $optionsHtml = '';
            if (!empty($item->options) && is_array($item->options)) {
                $parts = [];
                foreach ($item->options as $option) {
                    $parts[] = ($option['name'] ?? '') . ': ' . ($option['value'] ?? '');
                }
                if (!empty($parts)) {
                    $optionsHtml = '<div style="font-size: 0.85em; color: #475569; margin-top: 2px;">' . implode(' | ', $parts) . '</div>';
                }
            }

            $itemVars = [
                '{item_name}' => $item->name,
                '{item_description}' => ($item->description ?? '') . $optionsHtml,
                '{item_options}' => $optionsHtml,
                '{item_image}' => $itemImageHtml,
                '{item_quantity}' => $item->quantity + 0,
                '{item_unit}' => $item->unit_type,
                '{item_unit_full}' => ($item->unitType ? $item->unitType->name . ': ' : '') . $item->unit_type,
                '{item_price}' => number_format($item->unit_price, 2),
                '{item_subtotal}' => number_format($item->calculated_subtotal, 2),
                '{item_tax_1_percent}' => $item->tax_1 + 0,
                '{item_tax_2_percent}' => $item->tax_2 + 0,
                '{item_tax_percent}' => ($item->tax_1 + 0) + ($item->tax_2 + 0),
                '{item_total}' => number_format($item->total, 2),
                '{item_comments}' => $commentsHtml,
                '{item_size}' => $item->size ?? '',
                '{item_unit_configuration}' => $item->unit_configuration,
                '{item_length}' => $item->length + 0,
                '{item_width}' => $item->width + 0,
                '{item_height}' => $item->height + 0,
                '{item_formula_label}' => $item->formula_label,
                '{item_unit_category}' => $item->unitType->name ?? '',
                '{item_unit_type}' => $item->unit_type ?? '',
                'item_is_package' => ($itemIsPackage = ($item->is_package || ($item->section && $item->section->is_package) || ($parentSection && $parentSection->is_package))) ? 1 : 0,
                '_raw_item_is_package' => $itemIsPackage ? 1 : 0,
                'section_is_package' => ($parentSection && $parentSection->is_package) ? 1 : 0,
                '_raw_section_is_package' => ($parentSection && $parentSection->is_package) ? 1 : 0,
                'has_length' => (!$itemIsPackage && $item->length > 0) ? 1 : 0,
                'has_width' => (!$itemIsPackage && $item->width > 0) ? 1 : 0,
                'has_height' => (!$itemIsPackage && $item->height > 0) ? 1 : 0,
                'has_size' => (!$itemIsPackage && $item->size > 0) ? 1 : 0,
                'has_dimensions' => (!$itemIsPackage && ($item->length > 0 || $item->width > 0 || $item->height > 0)) ? 1 : 0,
                'has_any_dimensions' => (!$itemIsPackage && ($item->length > 0 || $item->width > 0 || $item->height > 0 || $item->size > 0)) ? 1 : 0,
                'has_image' => ($item->product && $item->product->images->isNotEmpty()) ? 1 : 0,
            ];

            // Process Item Conditionals
            $itemHtml = $this->parseConditionals($itemHtml, $itemVars);

            foreach ($itemVars as $key => $value) {
                // Support both {key}, { key }, and encoded %7Bkey%7D
                $strippedKey = trim($key, '{} ');
                $itemHtml = str_replace([
                    '{' . $strippedKey . '}',
                    '{ ' . $strippedKey . ' }',
                    '%7B' . $strippedKey . '%7D'
                ], $value, $itemHtml);
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

    public static function getAvailableVariables()
    {
        $registry = \App\Services\Pdf\VariableRegistry::all();
        $grouped = [];

        foreach ($registry as $key => $def) {
            $category = $def['category'] ?? 'General';
            // Format: variable_name => Description
            $grouped[$category][$key] = $def['description'];
        }

        return $grouped;
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
