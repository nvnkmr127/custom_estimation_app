<?php

namespace Database\Seeders;

use App\Models\PdfTemplate;
use Illuminate\Database\Seeder;

class PdfTemplateSeeder extends Seeder
{
    public function run()
    {
        // Modern Template
        PdfTemplate::updateOrCreate(
            ['name' => 'Modern'],
            [
                'type' => 'system',
                'description' => 'A clean, modern design with bold headers and clear structure.',
                'html_content' => '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{estimate_title}</title>
</head>
<body>
    <div class="header">
        <div class="logo">
            <h1>{company_name}</h1>
        </div>
        <div class="meta">
            <p style="font-weight: 800; font-size: 16px; margin: 0; color: #0f172a;">ESTIMATE #{estimate_number}</p>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 12px;">Date: {estimate_date} | Expiry: {expiry_date}</p>
        </div>
    </div>

    <div class="client-info">
        <h3 style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.1em; color: #94a3b8; margin-bottom: 8px;">Prepared For</h3>
        <p style="font-size: 16px; font-weight: 700; margin: 0; color: #0f172a;">{client_name}</p>
        <p style="margin: 4px 0 0 0; color: #475569; font-size: 13px;">{client_address}</p>
        <p style="margin: 2px 0 0 0; color: #475569; font-size: 13px;">{client_email}</p>
    </div>

    <div class="section" style="margin-top: 40px;">
        <h2 style="font-size: 18px;">Project Overview</h2>
        <p style="font-size: 14px; color: #475569;">{estimate_title}</p>
    </div>

    {IF_room_based}
    <div class="section page-break-inside-avoid">
        <h3>Room Distribution</h3>
        <div style="text-align: center; background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0;">
            <img src="{CHART_ROOMS}" alt="Room Cost Chart" style="width: 100%; max-width: 500px;">
        </div>
    </div>
    {END_IF}

    <div class="section">
        <h2 style="font-size: 18px;">Itemized Breakdown</h2>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="10%">Image</th>
                    <th width="15%">Unit Config</th>
                    <th width="30%">Item Details</th>
                    <th width="10%" class="text-center">Size</th>
                    <th width="10%" class="text-right">Price</th>
                    <th width="10%" class="text-center">Quantity</th>
                    <th width="15%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                {LOOP_SECTIONS}
                <tr class="section-header">
                    <td colspan="7">{section_name}</td>
                </tr>
                    {LOOP_ITEMS}
                    <tr>
                        <td class="text-center">{item_image}</td>
                        <td style="font-size: 10px; color: #64748b;">{item_unit_configuration}</td>
                        <td>
                            <span class="item-name">{item_name}</span>
                            <div class="item-desc">{item_description}</div>
                            {item_comments}
                        </td>
                        <td class="text-center">{item_size}</td>
                        <td class="text-right" style="color: #475569;">{currency} {item_price}</td>
                        <td class="text-center" style="font-weight: 700; color: #0f172a;">
                            {item_quantity}<br>
                            <span style="font-size: 9px; color: #94a3b8; text-transform: uppercase;">{item_unit}</span>
                        </td>
                        <td class="text-right" style="font-weight: 800; color: #0f172a;">{currency} {item_total}</td>
                    </tr>
                    {END_LOOP}
                <tr>
                    <td colspan="6" class="text-right" style="padding: 10px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Room Total</td>
                    <td class="text-right" style="padding: 10px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #e2e8f0;">{currency} {section_subtotal}</td>
                </tr>
                {END_LOOP_SECTIONS}
                
                {IF_NOT_room_based}
                    {LOOP_ITEMS}
                    <tr>
                        <td class="text-center">{item_image}</td>
                        <td style="font-size: 10px; color: #64748b;">{item_unit_configuration}</td>
                        <td>
                            <span class="item-name">{item_name}</span>
                            <div class="item-desc">{item_description}</div>
                            {item_comments}
                        </td>
                        <td class="text-center">{item_size}</td>
                        <td class="text-right" style="color: #475569;">{currency} {item_price}</td>
                        <td class="text-center" style="font-weight: 700; color: #0f172a;">
                            {item_quantity}<br>
                            <span style="font-size: 9px; color: #94a3b8; text-transform: uppercase;">{item_unit}</span>
                        </td>
                        <td class="text-right" style="font-weight: 800; color: #0f172a;">{currency} {item_total}</td>
                    </tr>
                    {END_LOOP}
                {END_IF}
            </tbody>
        </table>
    </div>

    <div class="totals py-6">
        <div style="width: 40%; margin-left: auto;">
            <p style="display: table; width: 100%;">
                <span style="display: table-cell; text-align: left;">Subtotal</span>
                <span style="display: table-cell; text-align: right; font-weight: 700;">{currency} {subtotal}</span>
            </p>
            {IF_tax_total}
            <p style="display: table; width: 100%;">
                <span style="display: table-cell; text-align: left;">Tax</span>
                <span style="display: table-cell; text-align: right; font-weight: 700;">{currency} {tax_total}</span>
            </p>
            {END_IF}
            {IF_discount_total}
            <p style="display: table; width: 100%; color: #ef4444;">
                <span style="display: table-cell; text-align: left;">Discount</span>
                <span style="display: table-cell; text-align: right; font-weight: 700;">-{currency} {discount_total}</span>
            </p>
            {END_IF}
            <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid var(--primary-color);">
                <h3 style="display: table; width: 100%; margin: 0;">
                    <span style="display: table-cell; text-align: left; font-size: 14px; text-transform: uppercase;">Grand Total</span>
                    <span style="display: table-cell; text-align: right;">{currency} {grand_total}</span>
                </h3>
            </div>
        </div>
    </div>

    <div class="section page-break-inside-avoid" style="margin-top: 50px;">
        <h2 style="font-size: 16px;">Terms & Conditions</h2>
        <div style="font-size: 12px; color: #475569; line-height: 1.6;">
            {terms}
        </div>
    </div>
    
    <div class="footer">
        <p style="margin-bottom: 4px; font-weight: 700;">Thank you for your business!</p>
        <p>{company_address} &bull; {company_email} &bull; {company_phone}</p>
    </div>
</body>
</html>',
                'css_content' => '
body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.5; margin: 0; padding: 0; }
.header { display: table; width: 100%; border-bottom: 2px solid var(--primary-color); padding-bottom: 20px; margin-bottom: 30px; }
.logo { display: table-cell; vertical-align: middle; }
.meta { display: table-cell; text-align: right; vertical-align: middle; }
.section { margin-bottom: 30px; }
h1 { color: var(--primary-color); margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.025em; }
h2 { color: var(--primary-color); border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-top: 0; font-weight: 700; }
h3 { margin-bottom: 10px; font-weight: 600; color: #334155; }
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; table-layout: fixed; }
.items-table th { background: #f8fafc; padding: 12px 10px; text-align: left; border-bottom: 2px solid #e2e8f0; font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; }
.items-table td { padding: 15px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
.section-header td { background: #f1f5f9; font-weight: 800; color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; padding: 8px 10px; }
.item-name { display: block; font-weight: 700; color: #0f172a; margin-bottom: 2px; font-size: 13px; }
.item-desc { color: #64748b; font-size: 11px; line-height: 1.4; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.totals { text-align: right; margin-top: 20px; padding-top: 20px; border-top: 2px solid #f1f5f9; }
.totals p { margin: 5px 0; color: #475569; font-size: 13px; }
.totals h3 { color: var(--primary-color); font-size: 20px; margin-top: 10px; font-weight: 800; }
.footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 15px; }
.page-break-inside-avoid { page-break-inside: avoid; }
',
                'primary_color' => '#2563eb', // Blue
                'secondary_color' => '#1e40af',
                'font_family' => 'Helvetica, sans-serif',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        // Premium Template
        PdfTemplate::updateOrCreate(
            ['name' => 'Premium'],
            [
                'type' => 'system',
                'description' => 'A luxurious design with gold accents and a sidebar.',
                'html_content' => '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{estimate_title}</title>
</head>
<body>
    <div class="sidebar"></div>
    <div class="content">
        <div class="header">
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; vertical-align: middle;">
                    <h1>ESTIMATE</h1>
                </div>
                <div style="display: table-cell; vertical-align: middle; text-align: right;">
                    <p style="font-size: 48px; font-weight: 800; color: #f1f5f9; margin: 0; position: absolute; top: -20px; right: 0; z-index: -1;">{estimate_number}</p>
                </div>
            </div>
            <div class="meta-grid">
                <div>
                    <span class="label">Reference:</span> {estimate_number}
                </div>
                <div>
                    <span class="label">Date:</span> {estimate_date}
                </div>
                <div>
                    <span class="label">Valid Until:</span> {expiry_date}
                </div>
            </div>
        </div>

        <div class="client-grid">
            <div class="box">
                <h6>ISSUED BY</h6>
                <p><strong>{company_name}</strong><br>
                {company_address}<br>
                {company_email}</p>
            </div>
            <div class="box">
                <h6>PREPARED FOR</h6>
                <p><strong>{client_name}</strong><br>
                {client_address}<br>
                {client_email}</p>
            </div>
        </div>

        <div class="intro">
            <h2>Luxury Interior Solutions</h2>
            <p style="font-size: 14px; color: #64748b; font-style: normal; margin-top: 10px;">{estimate_title}</p>
        </div>

        {IF_room_based}
        <div class="chart-section">
            <h3 style="font-family: sans-serif; font-size: 12px; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8; margin-bottom: 20px;">Investment Analysis</h3>
            <img src="{CHART_ROOMS}" alt="Visual Breakdown">
            <p class="caption">Investment distribution across different areas</p>
        </div>
        {END_IF}

        <div class="items-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th width="10%"></th>
                        <th width="15%" class="text-left">Details</th>
                        <th width="30%">Description</th>
                        <th width="10%" class="text-center">Size</th>
                        <th width="10%" class="text-right">Price</th>
                        <th width="10%" class="text-center">Qty</th>
                        <th width="15%" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    {LOOP_SECTIONS}
                    <tr class="section-row">
                        <td colspan="7">{section_name}</td>
                    </tr>
                        {LOOP_ITEMS}
                        <tr>
                            <td class="text-center">{item_image}</td>
                            <td style="font-size: 10px; color: #94a3b8; font-family: monospace;">{item_unit_configuration}</td>
                            <td>
                                <span class="item-name">{item_name}</span>
                                <div class="item-desc">{item_description}</div>
                                {item_comments}
                            </td>
                            <td class="text-center" style="font-size: 10px;">{item_size}</td>
                            <td class="text-right" style="color: #475569;">{currency} {item_price}</td>
                            <td class="text-center" style="font-weight: 700; color: #1e293b;">
                                {item_quantity}<br>
                                <span style="font-size: 9px; color: #94a3b8; text-transform: uppercase;">{item_unit}</span>
                            </td>
                            <td class="text-right" style="font-weight: 800; color: #0f172a;">{currency} {item_total}</td>
                        </tr>
                        {END_LOOP}
                    <tr>
                        <td colspan="6" class="text-right sub-label">Subtotal ({section_name})</td>
                        <td class="text-right sub-val">{currency} {section_subtotal}</td>
                    </tr>
                    {END_LOOP_SECTIONS}
                    
                    {IF_NOT_room_based}
                        {LOOP_ITEMS}
                        <tr>
                            <td class="text-center">{item_image}</td>
                            <td style="font-size: 10px; color: #94a3b8; font-family: monospace;">{item_unit_configuration}</td>
                            <td>
                                <span class="item-name">{item_name}</span>
                                <div class="item-desc">{item_description}</div>
                                {item_comments}
                            </td>
                            <td class="text-center" style="font-size: 10px;">{item_size}</td>
                            <td class="text-center" style="font-weight: 700; color: #1e293b;">
                                {item_quantity}<br>
                                <span style="font-size: 9px; color: #94a3b8; text-transform: uppercase;">{item_unit}</span>
                            </td>
                            <td class="text-right" style="color: #475569;">{currency} {item_price}</td>
                            <td class="text-right" style="font-weight: 800; color: #0f172a;">{currency} {item_total}</td>
                        </tr>
                        {END_LOOP}
                    {END_IF}
                </tbody>
            </table>
        </div>

        <div class="summary-section">
            <table class="summary-table">
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right">{currency} {subtotal}</td>
                </tr>
                {IF_tax_total}
                <tr>
                    <td>Tax</td>
                    <td class="text-right">{currency} {tax_total}</td>
                </tr>
                {END_IF}
                {IF_discount_total}
                <tr>
                    <td style="color: #ef4444;">Discount</td>
                    <td class="text-right" style="color: #ef4444;">-{currency} {discount_total}</td>
                </tr>
                {END_IF}
                <tr class="grand-total-row">
                    <td>GRAND TOTAL</td>
                    <td class="text-right">{currency} {grand_total}</td>
                </tr>
            </table>
        </div>

        <div class="terms-section page-break-inside-avoid">
            <h6>AUTHORIZATION & TERMS</h6>
            <div class="terms-content">
                {terms}
            </div>
            <div class="signature-box" style="margin-top: 40px;">
                <div style="display: table; width: 100%;">
                    <div style="display: table-cell; width: 45%;">
                        <div class="line"></div>
                        <span style="font-family: sans-serif; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8;">For {company_name}</span>
                    </div>
                    <div style="display: table-cell; width: 10%;"></div>
                    <div style="display: table-cell; width: 45%;">
                        <div class="line"></div>
                        <span style="font-family: sans-serif; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8;">Accepted By</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>',
                'css_content' => '
body { margin: 0; padding: 0; font-family: "Georgia", serif; color: #444; }
.sidebar { position: fixed; top: 0; bottom: 0; left: 0; width: 40px; background: var(--primary-color); }
.content { margin-left: 60px; padding: 40px; }
.header h1 { font-family: sans-serif; letter-spacing: 4px; color: var(--primary-color); font-size: 32px; border-bottom: 2px solid var(--secondary-color); padding-bottom: 10px; margin-bottom: 20px; }
.meta-grid { display: block; margin-bottom: 40px; }
.meta-grid div { display: inline-block; width: 30%; margin-right: 2%; font-family: sans-serif; font-size: 11px; text-transform: uppercase; color: #888; }
.meta-grid .label { font-weight: bold; color: #333; }

.client-grid { display: table; width: 100%; margin-bottom: 40px; }
.client-grid .box { display: table-cell; width: 45%; vertical-align: top; padding: 20px; background: #f9f9f9; border-left: 3px solid var(--primary-color); }
.client-grid .box:first-child { padding-right: 5%; }
.box h6 { margin: 0 0 10px 0; font-family: sans-serif; font-size: 10px; letter-spacing: 2px; color: #999; text-transform: uppercase; }
.box p { margin: 0; font-size: 12px; line-height: 1.5; }

.intro { margin-bottom: 40px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
.intro h2 { font-size: 18px; color: var(--primary-color); margin-bottom: 10px; }
.intro p { font-style: italic; font-size: 13px; color: #666; }

.chart-section { text-align: center; margin-bottom: 40px; padding: 20px; border: 1px solid #eee; background: #fff; }
.chart-section img { max-width: 80%; height: auto; }
.caption { font-size: 10px; color: #999; margin-top: 5px; font-style: italic; }

.premium-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
.premium-table th { font-family: sans-serif; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #fff; background: #333; padding: 12px; }
.premium-table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 12px; }
.section-row td { background: var(--primary-color); color: #fff; font-weight: bold; font-family: sans-serif; font-size: 11px; padding: 8px 12px; }
.item-name { display: block; font-weight: bold; color: #333; margin-bottom: 3px; }
.item-desc { color: #777; font-size: 11px; }

.text-right { text-align: right; }
.text-center { text-align: center; }

.sub-label { font-family: sans-serif; font-size: 10px; text-transform: uppercase; font-weight: bold; color: #888; border-bottom: none; }
.sub-val { font-weight: bold; color: #333; border-bottom: none; }

.summary-section { width: 40%; margin-left: auto; }
.summary-table { width: 100%; border-collapse: collapse; }
.summary-table td { padding: 8px 0; border-bottom: 1px solid #eee; font-size: 12px; }
.grand-total-row td { border-bottom: 2px solid var(--primary-color); border-top: 2px solid var(--primary-color); font-weight: bold; font-size: 14px; padding: 15px 0; color: var(--primary-color); }

.terms-section { margin-top: 50px; background: #fdfdfd; border: 1px solid #eee; padding: 20px; page-break-inside: avoid; }
.terms-content { font-size: 10px; color: #666; line-height: 1.6; margin-bottom: 40px; columns: 2; }
.signature-box { width: 200px; }
.signature-box .line { border-bottom: 1px solid #333; margin-bottom: 5px; height: 1px; }
.signature-box span { font-size: 10px; text-transform: uppercase; color: #888; }
',
                'primary_color' => '#1a1a1a', // Black/Dark Grey
                'secondary_color' => '#c0a062', // Goldish
                'font_family' => 'Dejavu Serif, serif',
                'is_active' => true,
            ]
        );

        // Minimalist Template
        PdfTemplate::updateOrCreate(
            ['name' => 'Minimalist'],
            [
                'type' => 'system',
                'description' => 'A stark, typewriter-style design focused on information.',
                'html_content' => '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{estimate_title}</title>
</head>
<body>
    <div class="header-minimal">
        <h1>ESTIMATE</h1>
        <div class="ref-date">
            <span>#{estimate_number}</span> &bull; <span>{estimate_date}</span>
        </div>
    </div>
    
    <div class="contact-minimal">
        <p><strong>{company_name}</strong> &mdash; {company_email}</p>
        <p>Prepared for: <strong>{client_name}</strong></p>
    </div>

    <div class="line-break"></div>

    <div class="items-minimal">
        <table width="100%">
            {LOOP_SECTIONS}
            <tr><td colspan="3" class="section-title">{section_name}</td></tr>
                {LOOP_ITEMS}
                <tr class="item-row">
                    <td width="55%">
                        <strong>{item_name}</strong>
                        <div class="dim">{item_description}</div>
                        <div class="dim" style="font-family: monospace;">{item_unit_configuration} | {item_size}</div>
                        {item_comments}
                    </td>
                    <td width="15%" class="text-center">{item_quantity} {item_unit}</td>
                    <td width="10%">{item_image}</td>
                    <td width="20%" class="text-right">{item_total}</td>
                </tr>
                {END_LOOP}
            {END_LOOP_SECTIONS}

            {IF_NOT_room_based}
                {LOOP_ITEMS}
                <tr class="item-row">
                    <td width="60%">
                        <strong>{item_name}</strong>
                        <div class="dim">{item_description}</div>
                        <div class="dim" style="font-family: monospace;">{item_unit_configuration} | {item_size}</div>
                        {item_comments}
                    </td>
                    <td width="15%" class="text-center">{item_quantity} {item_unit}</td>
                    <td width="25%" class="text-right">{item_total}</td>
                </tr>
                {END_LOOP}
            {END_IF}
        </table>
    </div>
    
    <div class="line-break"></div>

    <div class="total-minimal">
        <h1>{currency} {grand_total}</h1>
        <p>Total Estimate</p>
    </div>

    <div class="footer-minimal">
        <p>{terms}</p>
        <p>&copy; {company_name}</p>
    </div>
</body>
</html>',
                'css_content' => '
body { font-family: "Courier New", Courier, monospace; color: #000; padding: 40px; }
.header-minimal { text-align: center; margin-bottom: 60px; }
.header-minimal h1 { font-weight: normal; letter-spacing: 5px; margin: 0; font-size: 24px; }
.ref-date { font-size: 10px; color: #555; margin-top: 10px; text-transform: uppercase; }

.contact-minimal { font-size: 12px; text-align: center; margin-bottom: 40px; }
.line-break { border-bottom: 1px dashed #000; margin: 40px 0; }

.items-minimal table { width: 100%; border-collapse: collapse; }
.section-title { font-weight: bold; padding: 20px 0 10px 0; border-bottom: 1px solid #000; font-size: 14px; text-transform: uppercase; }
.item-row td { padding: 15px 0; vertical-align: top; }
.dim { color: #666; font-size: 10px; margin-top: 4px; }
.text-right { text-align: right; }
.text-center { text-align: center; }

.total-minimal { text-align: center; margin: 60px 0; }
.total-minimal h1 { font-size: 48px; margin: 0; font-weight: normal; }
.total-minimal p { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; }

.footer-minimal { font-size: 10px; text-align: center; color: #888; margin-top: 50px; }
',
                'primary_color' => '#000000',
                'secondary_color' => '#333333',
                'font_family' => 'Courier New',
                'is_active' => true,
            ]
        );
    }
}
