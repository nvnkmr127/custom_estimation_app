<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PdfTemplate;
use App\Models\Setting;

class PdfTemplateSeeder extends Seeder
{
    public function run()
    {
        // Ensure settings exist (in case FullDummyContentSeeder wasn't run)
        $settings = [
            'company_legal_name' => 'Premium Interiors Pvt Ltd',
            'company_email' => 'contact@premiuminteriors.com',
            'company_phone' => '+91 98765 43210',
            'company_address_street' => '123, Luxury Avenue, Jubilee Hills',
            'company_address_city' => 'Hyderabad',
            'company_address_state' => 'Telangana',
            'company_website' => 'www.premiuminteriors.com',
            'company_about' => 'We are a premier interior design firm dedicated to transforming spaces into bespoke experiences. With over 15 years of excellence, we specialize in high-end residential and commercial projects that blend functionality with artistic vision.',
            'company_profile' => 'Founded in 2010, Premium Interiors has delivered 500+ projects across India. Our team of expert architects and designers ensures that every corner of your home reflects your personality. We use only top-grade materials like Italian Marble, Teak Wood, and premium fittings. Innovation and Sustainability are at the core of our designs.',
        ];
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // 1. Premium Template
        $premiumCss = "
            .header-bg { background-color: var(--primary-color); color: #fff; padding: 40px; }
            .company-title { font-size: 28px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
            .meta-table { width: 100%; margin-top: 20px; color: #fff; }
            .meta-table td { padding: 5px; }
            .section-title { color: var(--primary-color); font-size: 16px; font-weight: bold; border-bottom: 2px solid var(--secondary-color); padding-bottom: 5px; margin-top: 30px; margin-bottom: 15px; }
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .items-table th { background-color: #f3f4f6; color: #555; font-weight: bold; padding: 10px; text-align: left; font-size: 12px; text-transform: uppercase; }
            .items-table td { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; }
            .total-row td { font-weight: bold; font-size: 14px; background-color: #f9fafb; }
            .summary-card { background: #f9fafb; padding: 20px; border-radius: 8px; margin-top: 20px; page-break-inside: avoid; }
            .chart-row { display: table; width: 100%; margin-bottom: 10px; }
            .chart-label { display: table-cell; width: 30%; font-size: 12px; font-weight: bold; color: #555; vertical-align: middle; }
            .chart-bar-cell { display: table-cell; width: 50%; vertical-align: middle; }
            .chart-value { display: table-cell; width: 20%; text-align: right; font-size: 12px; font-weight: bold; color: var(--primary-color); vertical-align: middle; }
            .bar-outer { height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
            .bar-inner { height: 100%; background: var(--secondary-color); }
            .about-section { margin-top: 40px; padding: 30px; background: #fff; border: 1px solid #eee; page-break-inside: avoid; }
            .about-title { font-size: 18px; color: var(--primary-color); font-weight: bold; margin-bottom: 15px; text-transform: uppercase; }
            .about-text { font-size: 12px; color: #666; line-height: 1.6; text-align: justify; }
            .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #aaa; border-top: 1px solid #eee; padding-top: 15px; }
        ";

        $premiumHtml = '
            <div class=\"header-bg\">
                <div class=\"company-title\">{company_name}</div>
                <div style=\"font-size: 12px; margin-top: 5px; opacity: 0.8;\">{company_address}, {company_city}</div>
                <div style=\"font-size: 12px; opacity: 0.8;\">{company_email} | {company_phone}</div>
                
                <table class=\"meta-table\">
                    <tr>
                        <td width=\"50%\">
                            <div style=\"font-size: 10px; text-transform: uppercase; opacity: 0.7;\">Prepared For</div>
                            <div style=\"font-size: 16px; font-weight: bold;\">Client #{client_id}</div>
                            <div style=\"font-size: 12px;\">{client_name}</div>
                        </td>
                        <td width=\"50%\" align=\"right\">
                            <div style=\"font-size: 30px; font-weight: 300;\">ESTIMATE</div>
                            <div style=\"font-size: 14px;\">#{estimate_number}</div>
                            <div style=\"font-size: 12px; margin-top: 5px;\">Date: {estimate_date}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div style=\"padding: 40px;\">
                {LOOP_SECTIONS}
                <div class=\"section-title\">{section_name}</div>
                <table class=\"items-table\">
                    <thead>
                        <tr>
                            <th width=\"45%\">Description</th>
                            <th width=\"10%\" align=\"center\">Qty</th>
                            <th width=\"15%\" align=\"right\">Unit Price</th>
                            <th width=\"15%\" align=\"right\">Tax</th>
                            <th width=\"15%\" align=\"right\">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {LOOP_ITEMS}
                        <tr>
                            <td>
                                <b>{item_name}</b>
                                <div style=\"font-size: 11px; color: #888;\">{item_description}</div>
                            </td>
                            <td align=\"center\">{item_quantity}</td>
                            <td align=\"right\">{item_price}</td>
                            <td align=\"right\">{item_tax_percent}%</td>
                            <td align=\"right\">{item_total}</td>
                        </tr>
                        {END_LOOP}
                    </tbody>
                </table>
                {END_LOOP}

                <!-- Room Cost Summary Chart -->
                <div class=\"summary-card\">
                    <div class=\"section-title\" style=\"margin-top: 0;\">Cost Distribution</div>
                    {LOOP_SECTIONS}
                    <div class=\"chart-row\">
                        <div class=\"chart-label\">{section_name}</div>
                        <div class=\"chart-bar-cell\">
                            <div class=\"bar-outer\">
                                <div class=\"bar-inner\" style=\"width: {section_percent}%;\"></div>
                            </div>
                        </div>
                        <div class=\"chart-value\">{currency} {section_subtotal}</div>
                    </div>
                    {END_LOOP}
                </div>

                <table style=\"width: 300px; margin-left: auto; margin-top: 30px;\">
                    <tr>
                        <td style=\"padding: 5px; font-weight: bold; color: #555;\">Subtotal</td>
                        <td style=\"padding: 5px; text-align: right; font-weight: bold;\">{currency} {subtotal}</td>
                    </tr>
                    <tr>
                        <td style=\"padding: 5px; color: #555;\">Tax</td>
                        <td style=\"padding: 5px; text-align: right;\">{currency} {tax_total}</td>
                    </tr>
                    <tr>
                        <td style=\"padding: 10px 5px; font-size: 18px; font-weight: bold; color: var(--primary-color); border-top: 2px solid #333;\">Grand Total</td>
                        <td style=\"padding: 10px 5px; font-size: 18px; font-weight: bold; color: var(--primary-color); border-top: 2px solid #333; text-align: right;\">{currency} {grand_total}</td>
                    </tr>
                </table>

                <!-- Company Profile Section -->
                <div class=\"about-section\">
                    <div class=\"about-title\">Who We Are</div>
                    <div class=\"about-text\">{company_about}</div>
                    <div style=\"margin-top: 15px;\" class=\"about-text\">{company_profile}</div>
                </div>

                <!-- Terms -->
                <div style=\"margin-top: 30px;\">
                    <div style=\"font-weight: bold; font-size: 12px; margin-bottom: 5px;\">Terms & Conditions</div>
                    <div style=\"font-size: 11px; color: #666; white-space: pre-wrap;\">{terms}</div>
                </div>

                <div class=\"footer\">
                    {company_website}
                </div>
            </div>
        ';

        PdfTemplate::updateOrCreate(
            ['name' => 'Premium Blue'],
            [
                'html_content' => $premiumHtml,
                'css_content' => $premiumCss,
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'primary_color' => '#1e3a8a', // Dark Blue
                'secondary_color' => '#3b82f6', // Light Blue
                'font_family' => 'Helvetica',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        // 2. Modern Template (Updated)
        // Similar structure but different styling, maybe green accent
        PdfTemplate::updateOrCreate(
            ['name' => 'Modern Green'],
            [
                'html_content' => $premiumHtml, // Reusing HTML for now but different colors
                'css_content' => $premiumCss,
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'primary_color' => '#065f46', // Dark Green
                'secondary_color' => '#10b981', // Emerald
                'font_family' => 'Helvetica',
                'is_active' => true,
                'is_default' => false,
            ]
        );
    }
}
