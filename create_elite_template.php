<?php

use App\Models\PdfTemplate;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$templateName = 'Elite Architectural Signature';

// Fully refined HTML with "Package vs Room" Size logic matching
$html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Inter', 'Helvetica', sans-serif;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background-color: transparent;
        }
        .document-page {
            background-color: #ffffff;
            padding: 40px;
            position: relative;
            min-height: 100vh;
            width: 100%;
            box-sizing: border-box;
        }
        .header-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 10px;
            background: var(--primary-color, #2563eb);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .item-table {
            width: 100%;
            margin-top: 10px;
            border: 1px solid #f1f5f9;
        }
        .item-table th {
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            color: #94a3b8;
            padding: 12px 10px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 800;
            text-align: left;
        }
        .item-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
            font-size: 11px;
            word-wrap: break-word;
        }
        .section-header {
            padding: 12px 15px;
            font-size: 14px;
            font-weight: 800;
            color: #1e293b;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-bottom: none;
            margin-top: 25px;
            border-radius: 8px 8px 0 0;
        }
        .section-footer {
            text-align: right;
            padding: 12px 15px;
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-top: none;
            margin-bottom: 25px;
            border-radius: 0 0 8px 8px;
        }
        .grand-total-box {
            background: var(--primary-color, #2563eb);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-top: 15px;
            text-align: center;
        }
        .col-image { width: 50px; text-align: center; }
        .col-config { width: 120px; text-align: center; }
        .col-details { width: auto; }
        .col-size { width: 95px; }
        .col-price { width: 85px; text-align: right; }
        .col-qty { width: 60px; text-align: center; }
        .col-total { width: 100px; text-align: right; }

        .dimension-row { margin-bottom: 4px; font-size: 10px; }
        .dimension-label { font-weight: 800; color: #94a3b8; width: 12px; display: inline-block; }
        .dimension-value { font-weight: 600; color: #1e293b; margin-left: 5px; }
        
        .formula-box { margin-top: 8px; padding-top: 6px; border-top: 1px solid #f1f5f9; }
        .formula-label { font-size: 8px; font-weight: 800; color: #4f46e5; text-transform: uppercase; margin-bottom: 2px; }
        .formula-value { font-size: 11px; font-weight: 800; color: #1e293b; }

        .item-image { width: 36px; height: 36px; border-radius: 6px; border: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="document-page">
        <div class="header-bg"></div>

        <!-- Header -->
        <div class="header" style="margin-bottom: 35px; min-height: 100px; padding-top: 20px;">
            <table>
                <tr>
                    <td style="width: 60%; vertical-align: top;">
                        <div style="margin-bottom: 15px;">{company_logo}</div>
                        <h1 style="margin: 0; color: var(--primary-color); font-size: 30px; font-weight: 900; letter-spacing: -0.02em;">{company_name}</h1>
                        <div style="margin-top: 8px; color: #64748b; font-size: 11px; line-height: 1.6;">
                            {company_address}<br>
                            <span style="font-weight: 700; color: #334155;">{company_email}</span> | {company_phone}
                        </div>
                    </td>
                    <td style="width: 40%; vertical-align: top; text-align: right;">
                        <div style="display: inline-block; text-align: left; min-width: 180px;">
                            <h2 style="color: #94a3b8; margin: 0 0 10px 0; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.15em;">Estimate</h2>
                            <div style="background: #f8fafc; padding: 18px; border-radius: 12px; border: 1px solid #f1f5f9;">
                                <div style="margin-bottom: 12px;">
                                    <div style="font-size: 8px; text-transform: uppercase; color: #94a3b8; font-weight: 800; letter-spacing: 0.1em; margin-bottom: 3px;">Reference Number</div>
                                    <div style="font-size: 18px; font-weight: 900; color: #0f172a;">#{estimate_number}</div>
                                </div>
                                <div style="height: 1px; background: #e2e8f0; margin-bottom: 12px;"></div>
                                <div>
                                    <div style="font-size: 8px; text-transform: uppercase; color: #94a3b8; font-weight: 800; letter-spacing: 0.1em; margin-bottom: 3px;">Issue Date</div>
                                    <div style="font-size: 12px; font-weight: 700; color: #475569;">{estimate_date}</div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Client Info -->
        <div style="margin-bottom: 40px;">
            <table>
                <tr>
                    <td style="width: 60%; vertical-align: top;">
                        <div style="border-left: 4px solid var(--secondary-color); padding-left: 15px;">
                            <div style="font-size: 9px; text-transform: uppercase; color: #94a3b8; font-weight: 800; letter-spacing: 0.15em; margin-bottom: 8px;">Prepared For</div>
                            <div style="font-size: 18px; font-weight: 900; color: #0f172a; margin-bottom: 4px;">{client_name}</div>
                            <div style="font-size: 12px; color: #64748b; line-height: 1.6;">
                                {client_address}<br>
                                {client_email}
                            </div>
                        </div>
                    </td>
                    <td style="width: 40%; text-align: right; vertical-align: middle;">
                        <img src="{CHART_SECTIONS_PIE}" style="max-height: 80px;" />
                    </td>
                </tr>
            </table>
        </div>

        <!-- Items Display -->
        {IF_room_based}
            {LOOP_SECTIONS}
                <div class="section-header">{section_name}</div>
                <table class="item-table" style="margin-top: 0; border-top: none;">
                    <thead>
                        <tr>
                            {IF_NOT_section_is_package}<th class="col-image">Image</th>{ENDIF}
                            {IF_NOT_section_is_package}<th class="col-config">Unit Configuration</th>{ENDIF}
                            <th class="col-details">Item Details</th>
                            {IF_NOT_section_is_package}<th class="col-size">Size</th>{ENDIF}
                            <th class="col-price">Price</th>
                            {IF_NOT_section_is_package}<th class="col-qty">Quantity</th>{ENDIF}
                            <th class="col-total">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {LOOP_ITEMS}
                        <tr>
                            {IF_NOT_section_is_package}<td class="col-image">{item_image}</td>{ENDIF}
                            {IF_NOT_section_is_package}
                            <td class="col-config">
                                <div style="font-weight: 800; color: #1e293b; font-size: 11px;">{item_unit_category}</div>
                                <div style="font-size: 8px; font-weight: 800; color: #94a3b8; text-transform: uppercase; mt-1;">{item_unit_type}</div>
                            </td>
                            {ENDIF}
                            <td class="col-details">
                                <div style="font-weight: 800; font-size: 12px; color: #0f172a; margin-bottom: 3px;">{item_name}</div>
                                <div style="font-size: 10px; color: #64748b; line-height: 1.4; margin-bottom: 6px;">{item_description}</div>
                                {item_comments}
                            </td>
                            {IF_NOT_section_is_package}
                            <td class="col-size">
                                {IF_has_length}<div class="dimension-row"><span class="dimension-label">L</span> <span class="dimension-value">{item_length} ft</span></div>{ENDIF}
                                {IF_has_width}<div class="dimension-row"><span class="dimension-label">W</span> <span class="dimension-value">{item_width} ft</span></div>{ENDIF}
                                {IF_has_height}<div class="dimension-row"><span class="dimension-label">H</span> <span class="dimension-value">{item_height} ft</span></div>{ENDIF}
                                {IF_has_size}
                                <div class="formula-box">
                                    <div class="formula-label">{item_formula_label}</div>
                                    <div class="formula-value">{item_size} <span style="font-size: 8px; color: #94a3b8;">{item_unit}</span></div>
                                </div>
                                {ENDIF}
                                {IF_NOT_has_any_dimensions}<span style="color: #cbd5e1;">-</span>{ENDIF}
                            </td>
                            {ENDIF}
                            <td class="col-price">{currency} {item_price}</td>
                            {IF_NOT_section_is_package}<td class="col-qty" style="font-weight: 800; color: #0f172a;">{item_quantity}</td>{ENDIF}
                            <td class="col-total" style="font-weight: 800; color: #0f172a;">{currency} {item_total}</td>
                        </tr>
                        {END_LOOP}
                    </tbody>
                </table>
                <div class="section-footer">
                    <span style="opacity: 0.7;">Section Total:</span> {currency} {section_total}
                </div>
            {END_LOOP_SECTIONS}
        {ENDIF}

        {IF_NOT_room_based}
            <table class="item-table">
                <thead>
                    <tr>
                        <th class="col-image">Image</th>
                        <th class="col-config">Unit Configuration</th>
                        <th class="col-details">Item Details</th>
                        <th class="col-size">Size</th>
                        <th class="col-price">Price</th>
                        <th class="col-qty">Quantity</th>
                        <th class="col-total">Total</th>
                    </tr>
                </thead>
                <tbody>
                    {LOOP_ITEMS}
                    <tr>
                        <td class="col-image">{item_image}</td>
                        <td class="col-config">
                            <div style="font-weight: 800; color: #1e293b; font-size: 11px;">{item_unit_category}</div>
                            <div style="font-size: 8px; font-weight: 800; color: #94a3b8; text-transform: uppercase; mt-1;">{item_unit_type}</div>
                        </td>
                        <td class="col-details">
                            <div style="font-weight: 800; font-size: 12px; color: #0f172a; margin-bottom: 3px;">{item_name}</div>
                            <div style="font-size: 10px; color: #64748b; line-height: 1.4; margin-bottom: 6px;">{item_description}</div>
                            {item_comments}
                        </td>
                        <td class="col-size">
                            {IF_has_length}<div class="dimension-row"><span class="dimension-label">L</span> <span class="dimension-value">{item_length} ft</span></div>{ENDIF}
                            {IF_has_width}<div class="dimension-row"><span class="dimension-label">W</span> <span class="dimension-value">{item_width} ft</span></div>{ENDIF}
                            {IF_has_height}<div class="dimension-row"><span class="dimension-label">H</span> <span class="dimension-value">{item_height} ft</span></div>{ENDIF}
                            {IF_has_size}
                            <div class="formula-box">
                                <div class="formula-label">{item_formula_label}</div>
                                <div class="formula-value">{item_size} <span style="font-size: 8px; color: #94a3b8;">{item_unit}</span></div>
                            </div>
                            {ENDIF}
                            {IF_NOT_has_any_dimensions}<span style="color: #cbd5e1;">-</span>{ENDIF}
                        </td>
                        <td class="col-price">{currency} {item_price}</td>
                        <td class="col-qty" style="font-weight: 800; color: #0f172a;">{item_quantity}</td>
                        <td class="col-total" style="font-weight: 800; color: #0f172a;">{currency} {item_total}</td>
                    </tr>
                    {END_LOOP}
                </tbody>
            </table>
        {ENDIF}

        <!-- Totals & Notes -->
        <div style="margin-top: 40px; page-break-inside: avoid;">
            <table>
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        {IF_terms}
                        <div style="padding-right: 40px;">
                            <h3 style="font-size: 10px; text-transform: uppercase; color: #94a3b8; margin: 0 0 12px 0; font-weight: 900; letter-spacing: 0.15em;">Terms & Conditions</h3>
                            <div style="font-size: 11px; color: #64748b; line-height: 1.7;">{terms}</div>
                        </div>
                        {END_IF}
                    </td>
                    <td style="width: 50%; vertical-align: top;">
                        <div style="background: #f8fafc; border-radius: 12px; padding: 25px; border: 1px solid #f1f5f9;">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding-bottom: 12px; font-size: 13px; color: #64748b; font-weight: 600;">Net Subtotal</td>
                                    <td style="padding-bottom: 12px; text-align: right; font-weight: 800; color: #1e293b; font-size: 15px;">{currency} {subtotal}</td>
                                </tr>
                                {IF_has_tax}
                                <tr>
                                    <td style="padding-bottom: 12px; font-size: 13px; color: #64748b; font-weight: 600;">Tax Computation</td>
                                    <td style="padding-bottom: 12px; text-align: right; font-weight: 800; color: #1e293b; font-size: 15px;">{currency} {tax_total}</td>
                                </tr>
                                {END_IF}
                                {IF_has_discount}
                                <tr>
                                    <td style="padding-bottom: 12px; font-size: 13px; color: #ef4444; font-weight: 600;">Special Discount</td>
                                    <td style="padding-bottom: 12px; text-align: right; font-weight: 800; color: #ef4444; font-size: 15px;">- {currency} {discount_total}</td>
                                </tr>
                                {END_IF}
                                <tr>
                                    <td colspan="2" style="padding-top: 20px;">
                                        <div class="grand-total-box">
                                            <table style="width: 100%;">
                                                <tr>
                                                    <td style="text-align: left; vertical-align: middle;">
                                                        <div style="font-size: 8px; text-transform: uppercase; font-weight: 900; letter-spacing: 0.2em; opacity: 0.8; line-height: 1;">Aggregate Total</div>
                                                    </td>
                                                    <td style="text-align: right; vertical-align: middle;">
                                                        <div style="font-size: 10px; font-weight: 900; opacity: 0.8; margin-bottom: 2px;">{currency}</div>
                                                        <div style="font-size: 26px; font-weight: 900; line-height: 1;">{grand_total}</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 50px; text-align: center; color: #94a3b8; font-size: 10px; font-weight: 700; border-top: 1px solid #f1f5f9; padding-top: 20px;">
            &copy; {company_name} | {company_email} | {company_phone}
        </div>
    </div>
</body>
</html>
HTML;

$css = <<<'CSS'
:root {
    --primary-color: #2563eb;
    --secondary-color: #64748b;
    --font-body: 'Inter';
}
CSS;

DB::beginTransaction();

try {
    $template = PdfTemplate::updateOrCreate(
        ['name' => $templateName],
        [
            'html_content' => $html,
            'css_content' => $css,
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'primary_color' => '#2563eb',
            'secondary_color' => '#64748b',
            'font_family' => 'Inter',
            'is_active' => true,
            'watermark_text' => 'PROPOSAL',
            'watermark_opacity' => 0.05,
        ]
    );

    DB::commit();
    echo "Successfully updated template: " . $template->name . " (ID: " . $template->id . ")\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error updating template: " . $e->getMessage() . "\n";
}
