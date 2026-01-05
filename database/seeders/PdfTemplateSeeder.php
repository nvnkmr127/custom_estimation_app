<?php

namespace Database\Seeders;

use App\Models\PdfTemplate;
use Illuminate\Database\Seeder;

class PdfTemplateSeeder extends Seeder
{
    public function run()
    {
        // Modern Template
        PdfTemplate::create([
            'name' => 'Modern',
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
            <!-- <img src="path/to/logo.png" alt="Company Logo"> -->
            <h1>{company_name}</h1>
        </div>
        <div class="meta">
            <p>Estimate #: {estimate_number}</p>
            <p>Date: {estimate_date}</p>
            <p>Expiry: {expiry_date}</p>
        </div>
    </div>

    <div class="client-info">
        <h3>Prepared For:</h3>
        <p><strong>{client_name}</strong></p>
        <p>{client_address}</p>
        <p>{client_email}</p>
    </div>

    <div class="section">
        <h2>About Us</h2>
        <p>We are a premium interior design firm dedicated to transforming spaces into living works of art. With over 10 years of experience, we specialize in modern, sustainable, and luxurious designs.</p>
    </div>

    <div class="section">
        <h2>Project Estimate</h2>
        <p>{estimate_title}</p>
        
        {IF_room_based}
        <div class="chart-container">
            <h3>Cost Distribution by Room</h3>
            <img src="{CHART_ROOMS}" alt="Room Cost Chart" style="width: 100%; max-width: 600px;">
        </div>
        {END_IF}

        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                {LOOP_SECTIONS}
                <tr class="section-header">
                    <td colspan="4">{section_name}</td>
                </tr>
                    {LOOP_ITEMS}
                    <tr>
                        <td>
                            <strong>{item_name}</strong><br>
                            <small>{item_description}</small>
                        </td>
                        <td class="text-right">{item_quantity} {item_unit}</td>
                        <td class="text-right">{currency} {item_price}</td>
                        <td class="text-right">{currency} {item_total}</td>
                    </tr>
                    {END_LOOP}
                <tr>
                    <td colspan="3" class="text-right"><strong>Subtotal ({section_name})</strong></td>
                    <td class="text-right"><strong>{currency} {section_subtotal}</strong></td>
                </tr>
                {END_LOOP}
                
                {IF_NOT_room_based}
                    {LOOP_ITEMS}
                    <tr>
                        <td>
                            <strong>{item_name}</strong><br>
                            <small>{item_description}</small>
                        </td>
                        <td class="text-right">{item_quantity} {item_unit}</td>
                        <td class="text-right">{currency} {item_price}</td>
                        <td class="text-right">{currency} {item_total}</td>
                    </tr>
                    {END_LOOP}
                {END_IF}
            </tbody>
        </table>
    </div>

    <div class="totals">
        <p>Subtotal: {currency} {subtotal}</p>
        <p>Tax: {currency} {tax_total}</p>
        <p>Discount: -{currency} {discount_total}</p>
        <h3>Grand Total: {currency} {grand_total}</h3>
    </div>

    <div class="section page-break-inside-avoid">
        <h2>Terms & Conditions</h2>
        <p>{terms}</p>
        <p>1. 50% advance payment required to start work.<br>
           2. Valid for 30 days from date of issue.<br>
           3. Timelines are subject to material availability.</p>
    </div>
    
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>{company_address} | {company_email} | {company_phone}</p>
    </div>
</body>
</html>',
            'css_content' => '
body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; }
.header { display: table; width: 100%; border-bottom: 2px solid var(--primary-color); padding-bottom: 20px; margin-bottom: 30px; }
.logo { display: table-cell; vertical-align: middle; }
.meta { display: table-cell; text-align: right; vertical-align: middle; }
.section { margin-bottom: 30px; }
h1 { color: var(--primary-color); margin: 0; font-size: 24px; }
h2 { color: var(--primary-color); border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 0; }
h3 { margin-bottom: 10px; }
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.items-table th { background: #f8f9fa; padding: 10px; text-align: left; border-bottom: 2px solid #ddd; }
.items-table td { padding: 10px; border-bottom: 1px solid #eee; }
.section-header td { background: #e9ecef; font-weight: bold; color: #495057; }
.text-right { text-align: right; }
.totals { text-align: right; margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee; }
.totals h3 { color: var(--primary-color); font-size: 20px; }
.footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #eee; padding-top: 10px; }
.page-break-inside-avoid { page-break-inside: avoid; }
',
            'primary_color' => '#2563eb', // Blue
            'secondary_color' => '#1e40af',
            'font_family' => 'Helvetica, sans-serif',
            'is_active' => true,
            'is_default' => true,
        ]);

        // Premium Template
        PdfTemplate::create([
            'name' => 'Premium',
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
            <h1>ESTIMATE</h1>
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
                {company_city}<br>
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
            <p>Thank you for considering us for your project. We have prepared this detailed estimate based on our discussion. This proposal uses premium materials to ensure elegance and durability.</p>
        </div>

        {IF_room_based}
        <div class="chart-section">
            <img src="{CHART_ROOMS}" alt="Visual Breakdown">
            <p class="caption">Investment distribution across different areas</p>
        </div>
        {END_IF}

        <div class="items-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th width="50%">Description</th>
                        <th width="15%" class="text-center">Qty</th>
                        <th width="15%" class="text-right">Unit Price</th>
                        <th width="20%" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    {LOOP_SECTIONS}
                    <tr class="section-row">
                        <td colspan="4">{section_name}</td>
                    </tr>
                        {LOOP_ITEMS}
                        <tr>
                            <td>
                                <span class="item-name">{item_name}</span>
                                <div class="item-desc">{item_description}</div>
                            </td>
                            <td class="text-center">{item_quantity} {item_unit}</td>
                            <td class="text-right">{item_price}</td>
                            <td class="text-right">{item_total}</td>
                        </tr>
                        {END_LOOP}
                    <tr>
                        <td colspan="3" class="text-right sub-label">Subtotal</td>
                        <td class="text-right sub-val">{currency} {section_subtotal}</td>
                    </tr>
                    {END_LOOP}
                    
                    {IF_NOT_room_based}
                        {LOOP_ITEMS}
                        <tr>
                            <td>
                                <span class="item-name">{item_name}</span>
                                <div class="item-desc">{item_description}</div>
                            </td>
                            <td class="text-center">{item_quantity} {item_unit}</td>
                            <td class="text-right">{item_price}</td>
                            <td class="text-right">{item_total}</td>
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
                <tr>
                    <td>Tax</td>
                    <td class="text-right">{currency} {tax_total}</td>
                </tr>
                {IF_discount_total}
                <tr>
                    <td>Discount</td>
                    <td class="text-right">({currency} {discount_total})</td>
                </tr>
                {END_IF}
                <tr class="grand-total-row">
                    <td>TOTAL ESTIMATE</td>
                    <td class="text-right">{currency} {grand_total}</td>
                </tr>
            </table>
        </div>

        <div class="terms-section page-break-inside-avoid">
            <h6>AUTHORIZATION & TERMS</h6>
            <div class="terms-content">
                {terms}
                <p>By signing below, you agree to the terms and conditions stated in this proposal.</p>
            </div>
            <div class="signature-box">
                <div class="line"></div>
                <span>Client Signature</span>
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
        ]);

        // Minimalist Template
        PdfTemplate::create([
            'name' => 'Minimalist',
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
                    <td width="60%">
                        <strong>{item_name}</strong>
                        <div class="dim">{item_description}</div>
                    </td>
                    <td width="15%" class="text-center">{item_quantity} {item_unit}</td>
                    <td width="25%" class="text-right">{item_total}</td>
                </tr>
                {END_LOOP}
            {END_LOOP}

            {IF_NOT_room_based}
                {LOOP_ITEMS}
                <tr class="item-row">
                    <td width="60%"><strong>{item_name}</strong><br><span class="dim">{item_description}</span></td>
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
        ]);
    }
}
