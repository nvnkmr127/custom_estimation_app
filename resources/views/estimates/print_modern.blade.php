<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate #{{ $estimate->estimate_number }}</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 14px;
        }

        .page-container {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 0 auto;
            position: relative;
            box-sizing: border-box;
        }

        @media print {
            body {
                background: none;
                -webkit-print-color-adjust: exact;
            }

            .page-container {
                width: 100%;
                height: auto;
                padding: 0;
                margin: 0;
                box-shadow: none;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }

        .logo {
            max-height: 80px;
            max-width: 200px;
            margin-bottom: 15px;
        }

        .company-info {
            font-size: 12px;
            color: #555;
            text-align: right;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #111;
            margin-bottom: 5px;
        }

        /* Document Info */
        .doc-title {
            font-size: 28px;
            font-weight: 300;
            color: #4F46E5;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .doc-meta {
            margin-bottom: 40px;
        }

        .doc-meta table {
            width: 100%;
            border-collapse: collapse;
        }

        .doc-meta td {
            padding: 5px 0;
            vertical-align: top;
        }

        .client-info {
            width: 60%;
        }

        .meta-data {
            width: 40%;
            text-align: right;
        }

        .label {
            font-size: 11px;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .value {
            font-weight: 600;
            font-size: 14px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            text-align: left;
            padding: 12px 10px;
            background-color: #F3F4F6;
            color: #374151;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 2px solid #E5E7EB;
        }

        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #E5E7EB;
            vertical-align: top;
        }

        .items-table .amount {
            text-align: right;
        }

        .items-table .center {
            text-align: center;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        /* Section Headers */
        .section-header td {
            background-color: #EEF2FF;
            color: #3730A3;
            font-weight: bold;
            padding: 8px 10px;
            font-size: 13px;
            border-bottom: 1px solid #E0E7FF;
        }

        /* Totals */
        .totals-container {
            display: flex;
            justify-content: flex-end;
            page-break-inside: avoid;
        }

        .totals-table {
            width: 300px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 0;
            text-align: right;
        }

        .totals-table .label {
            color: #666;
            font-size: 12px;
        }

        .totals-table .value {
            font-weight: 600;
            font-size: 14px;
            color: #111;
        }

        .totals-table .grand-total td {
            padding-top: 15px;
            border-top: 2px solid #333;
        }

        .totals-table .grand-total .value {
            font-size: 18px;
            color: #4F46E5;
        }

        /* Footer */
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            font-size: 11px;
            color: #888;
            text-align: center;
        }

        .notes-section {
            margin-top: 40px;
            padding: 20px;
            background-color: #F9FAFB;
            border-radius: 4px;
            page-break-inside: avoid;
        }

        .notes-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 5px;
            color: #111;
        }

        .notes-content {
            font-size: 12px;
            color: #555;
            white-space: pre-wrap;
        }

        /* Utilities */
        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .print-fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #4F46E5;
            color: white;
            padding: 12px 24px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            z-index: 100;
        }

        .print-fab:hover {
            background: #4338CA;
        }

        .print-fab svg {
            width: 20px;
            height: 20px;
        }
    </style>
</head>

<body>
    <button onclick="window.print()" class="print-fab no-print">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        Print / Save as PDF
    </button>

    <div class="page-container">
        <!-- Header -->
        <div class="header">
            <div>
                @if(isset($settings['app_logo']))
                    <img src="{{ asset($settings['app_logo']) }}" class="logo" alt="Logo">
                @else
                    <h1 class="company-name" style="font-size: 24px; color: #4F46E5;">
                        {{ $settings['app_name'] ?? config('app.name') }}</h1>
                @endif
            </div>
            <div class="company-info">
                <div class="company-name">
                    {{ $settings['company_legal_name'] ?? ($settings['app_name'] ?? config('app.name')) }}</div>
                @if(!empty($settings['company_address_street']))
                    <div>{{ $settings['company_address_street'] }}</div>
                    <div>
                        {{ $settings['company_address_city'] }}{{ !empty($settings['company_address_state']) ? ', ' . $settings['company_address_state'] : '' }}
                        {{ $settings['company_address_zip'] ?? '' }}
                    </div>
                @endif
                <div>{{ $settings['company_email'] ?? '' }}</div>
                <div>{{ $settings['company_phone'] ?? '' }}</div>
                @if(!empty($settings['company_tax_id']))
                    <div>Tax ID: {{ $settings['company_tax_id'] }}</div>
                @endif
            </div>
        </div>

        <!-- Meta -->
        <div class="doc-meta">
            <table>
                <tr>
                    <td class="client-info">
                        <div class="label">Bill To</div>
                        <div class="value" style="font-size: 16px; margin-bottom: 2px;">Client ID:
                            {{ $estimate->client_id }}</div>
                        <!-- Ideally Client Name/Address would be here if we had a Client Relation -->
                    </td>
                    <td class="meta-data">
                        <div class="doc-title">Estimate</div>
                        <div style="margin-bottom: 10px; color: #666;">#{{ $estimate->estimate_number }}</div>

                        <table>
                            <tr>
                                <td class="text-right">
                                    <div class="label">Date</div>
                                    <div class="value">
                                        {{ \Carbon\Carbon::parse($estimate->estimate_date)->format('M d, Y') }}</div>
                                </td>
                                <td class="text-right" style="padding-left: 20px;">
                                    <div class="label">Valid Until</div>
                                    <div class="value">
                                        {{ $estimate->expiry_date ? \Carbon\Carbon::parse($estimate->expiry_date)->format('M d, Y') : 'N/A' }}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%">Description</th>
                    <th class="center" style="width: 10%">Unit</th>
                    <th class="center" style="width: 10%">Qty</th>
                    <th class="amount" style="width: 15%">Price</th>
                    <th class="amount" style="width: 15%">Tax</th>
                    <th class="amount" style="width: 10%">Total</th>
                </tr>
            </thead>
            <tbody>
                @if($estimate->type === 'room_based')
                    @foreach($estimate->sections as $section)
                        <tr class="section-header">
                            <td colspan="6">{{ $section->name }}</td>
                        </tr>
                        @foreach($section->items as $item)
                            <tr>
                                <td>
                                    <div><b>{{ $item->name }}</b></div>
                                    @if($item->description)
                                        <div style="font-size: 11px; color: #666; margin-top: 2px;">{{ $item->description }}</div>
                                    @endif
                                    @if($item->length && $item->width)
                                        <div style="font-size: 11px; color: #555; margin-top: 2px;">
                                            Dims: {{ $item->length + 0 }} &times; {{ $item->width + 0 }}
                                        </div>
                                    @endif
                                </td>
                                <td class="center">{{ $item->unit_type }}</td>
                                <td class="center">{{ $item->quantity + 0 }}</td>
                                <td class="amount">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="amount">
                                    @php 
                                        $itemTax = ($item->unit_price * $item->quantity) * (($item->tax_1 + $item->tax_2) / 100);
                                    @endphp
                                    <div style="font-size: 10px; color: #888;">{{ $item->tax_1 + $item->tax_2 }}%</div>
                                    {{ number_format($itemTax, 2) }}
                                </td>
                                <td class="amount font-bold">{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                                <!-- Section Subtotal Optional -->
                    @endforeach
                @else
                   @foreach($estimate->items as $item)
                    <tr>
                         <td>
                            <div><b>{{ $item->name }}</b></div>
                            @if($item->description)
                                <div style="font-size: 11px; color: #666; margin-top: 2px;">{{ $item->description }}</div>
                            @endif
                            @if($item->length && $item->width)
                                <div style="font-size: 11px; color: #555; margin-top: 2px;">
                                    Dims: {{ $item->length + 0 }} &times; {{ $item->width + 0 }}
                                </div>
                            @endif
                        </td>
                        <td class="center">{{ $item->unit_type }}</td>
                        <td class="center">{{ $item->quantity + 0 }}</td>
                       <td class="amount">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="amount">
                            @php 
                                $itemTax = ($item->unit_price * $item->quantity) * (($item->tax_1 + $item->tax_2) / 100);
                            @endphp
                            <div style="font-size: 10px; color: #888;">{{ $item->tax_1 + $item->tax_2 }}%</div>
                             {{ number_format($itemTax, 2) }}
                            </td>
                            <td class="amount font-bold">{{ number_format($item->total, 2) }}</td>
                        </tr>
                @endforeach
            @endif
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-container">
            <table class="totals-table">
                <tr>
                    <td><span class="label">Subtotal</span></td>
                    <td><span class="value">{{ $estimate->currency }} {{ number_format($estimate->subtotal, 2) }}</span></td>
                </tr>
                @if($estimate->discount_total > 0)
                    <tr>
                        <td><span class="label">Discount</span></td>
                        <td><span class="value" style="color: #EF4444;">- {{ $estimate->currency }} {{ number_format($estimate->discount_total, 2) }}</span></td>
                    </tr>
                @endif
                @if($estimate->total_tax > 0)
                    <tr>
                        <td><span class="label">Total Tax</span></td>
                        <td><span class="value">{{ $estimate->currency }} {{ number_format($estimate->total_tax, 2) }}</span></td>
                    </tr>
                @endif
                <tr class="grand-total">
                    <td><span class="label" style="font-weight: bold; color: #111;">Grand Total</span></td>
                    <td><span class="value">{{ $estimate->currency }} {{ number_format($estimate->grand_total, 2) }}</span></td>
                </tr>
            </table>
        </div>

        @if($estimate->type === 'room_based')
        <!-- Summary Chart (New) -->
        <div style="margin-top: 30px; background: #fff; border: 1px solid #eee; padding: 20px; border-radius: 8px; page-break-inside: avoid;">
            <div style="font-weight: bold; color: #4F46E5; margin-bottom: 15px; font-size: 14px;">Cost Distribution</div>
            @php $gt = $estimate->grand_total > 0 ? $estimate->grand_total : 1; @endphp
            @foreach($estimate->sections as $section)
                @php $pct = ($section->subtotal / $gt) * 100; @endphp
                <div style="display: flex; align-items: center; margin-bottom: 8px;">
                    <div style="width: 30%; font-size: 12px; font-weight: 500;">{{ $section->name }}</div>
                    <div style="flex: 1; margin: 0 15px; height: 8px; background: #f3f4f6; border-radius: 4px; overflow: hidden;">
                        <div style="height: 100%; background: #4F46E5; width: {{ $pct }}%;"></div>
                    </div>
                    <div style="width: 20%; text-align: right; font-size: 12px; font-weight: bold;">{{ $estimate->currency }} {{ number_format($section->subtotal, 2) }}</div>
                </div>
            @endforeach
        </div>
        @endif

        <!-- About Us (New) -->
        <div style="margin-top: 30px; padding: 20px; background-color: #F9FAFB; border-radius: 4px; page-break-inside: avoid;">
             <div style="font-weight: bold; font-size: 14px; margin-bottom: 10px; color: #111;">About Us</div>
             <div style="font-size: 12px; color: #555; line-height: 1.6;">
                {{ \App\Models\Setting::getCached('company_about') }}
             </div>
        </div>

        <!-- Notes -->
        @if($estimate->client_note || $estimate->terms)
            <div class="notes-section">
                @if($estimate->client_note)
                    <div style="margin-bottom: 20px;">
                       <div class="notes-title">Note</div>
                        <div class="notes-content">{{ $estimate->client_note }}</div>
                    </div>
                @endif
                 @if($estimate->terms)
                    <div>
                        <div class="notes-title">Terms & Conditions</div>
                        <div class="notes-content">{{ $estimate->terms }}</div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            @if(!empty($settings['company_website']))
                 <p>{{ $settings['company_website'] }}</p>
            @endif
        </div>
    </div>
</body>
</html>
