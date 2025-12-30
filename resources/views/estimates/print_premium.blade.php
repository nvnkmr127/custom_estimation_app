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
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 14px;
        }

        .page-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            position: relative;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .page-container {
                width: 100%;
                box-shadow: none;
                margin: 0;
            }
        }

        /* Premium Theme Styles */
        :root {
            --primary: #1e3a8a;
            --secondary: #3b82f6;
        }

        .header-bg {
            background-color: var(--primary);
            color: #fff;
            padding: 40px;
        }

        .company-title {
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .meta-table {
            width: 100%;
            margin-top: 20px;
            color: #fff;
        }

        .meta-table td {
            padding: 5px;
            vertical-align: top;
        }

        .section-title {
            color: var(--primary);
            font-size: 16px;
            font-weight: bold;
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background-color: #f3f4f6;
            color: #555;
            font-weight: bold;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .amount {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .summary-card {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .chart-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .chart-label {
            width: 30%;
            font-size: 12px;
            font-weight: bold;
            color: #555;
        }

        .chart-bar-cell {
            flex: 1;
            margin: 0 10px;
        }

        .chart-value {
            width: 20%;
            text-align: right;
            font-size: 12px;
            font-weight: bold;
            color: var(--primary);
        }

        .bar-outer {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .bar-inner {
            height: 100%;
            background: var(--secondary);
        }

        .about-section {
            margin-top: 40px;
            padding: 30px;
            background: #fff;
            border: 1px solid #eee;
            page-break-inside: avoid;
        }

        .about-title {
            font-size: 18px;
            color: var(--primary);
            font-weight: bold;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .about-text {
            font-size: 12px;
            color: #666;
            line-height: 1.6;
            text-align: justify;
            margin-bottom: 10px;
        }

        .totals-table {
            width: 300px;
            margin-left: auto;
            margin-top: 30px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 5px;
            text-align: right;
        }

        .grand-total {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary);
            border-top: 2px solid #333;
            padding-top: 10px;
        }

        .print-fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            z-index: 100;
        }
    </style>
</head>

<body>
    <button onclick="window.print()" class="print-fab no-print">Print / PDF</button>

    <div class="page-container">
        <div class="header-bg">
            <div class="company-title">{{ \App\Models\Setting::getCached('company_legal_name') }}</div>
            <div style="font-size: 12px; margin-top: 5px; opacity: 0.8;">
                {{ \App\Models\Setting::getCached('company_address_street') }},
                {{ \App\Models\Setting::getCached('company_address_city') }}
            </div>
            <div style="font-size: 12px; opacity: 0.8;">
                {{ \App\Models\Setting::getCached('company_email') }} |
                {{ \App\Models\Setting::getCached('company_phone') }}
            </div>

            <table class="meta-table">
                <tr>
                    <td width="50%">
                        <div style="font-size: 10px; text-transform: uppercase; opacity: 0.7;">Prepared For</div>
                        <div style="font-size: 16px; font-weight: bold;">Client #{{ $estimate->client_id }}</div>
                        <div style="font-size: 12px;">{{ $estimate->client->name ?? 'Valued Client' }}</div>
                    </td>
                    <td width="50%" align="right">
                        <div style="font-size: 30px; font-weight: 300;">ESTIMATE</div>
                        <div style="font-size: 14px;">#{{ $estimate->estimate_number }}</div>
                        <div style="font-size: 12px; margin-top: 5px;">Date:
                            {{ $estimate->estimate_date->format('M d, Y') }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div style="padding: 40px;">
            @php
                $grandTotal = $estimate->grand_total > 0 ? $estimate->grand_total : 1;
            @endphp

            @if($estimate->type === 'room_based')
                @foreach($estimate->sections as $section)
                    <div class="section-title">{{ $section->name }}</div>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th width="45%">Description</th>
                                <th width="10%" class="center">Qty</th>
                                <th width="15%" class="amount">Unit Price</th>
                                <th width="15%" class="amount">Tax</th>
                                <th width="15%" class="amount">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($section->items as $item)
                                <tr>
                                    <td>
                                        <b>{{ $item->name }}</b>
                                        <div style="font-size: 11px; color: #888;">{{ $item->description }}</div>
                                    </td>
                                    <td class="center">{{ $item->quantity + 0 }}</td>
                                    <td class="amount">{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="amount">{{ $item->tax_1 + $item->tax_2 }}%</td>
                                    <td class="amount">{{ number_format($item->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach

                <!-- Chart -->
                <div class="summary-card">
                    <div class="section-title" style="margin-top: 0;">Cost Distribution</div>
                    @foreach($estimate->sections as $section)
                        @php $percent = ($section->subtotal / $grandTotal) * 100; @endphp
                        <div class="chart-row">
                            <div class="chart-label">{{ $section->name }}</div>
                            <div class="chart-bar-cell">
                                <div class="bar-outer">
                                    <div class="bar-inner" style="width: {{ $percent }}%;"></div>
                                </div>
                            </div>
                            <div class="chart-value">{{ $estimate->currency }} {{ number_format($section->subtotal, 2) }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Standard Items Table -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th width="45%">Description</th>
                            <th width="10%" class="center">Qty</th>
                            <th width="15%" class="amount">Unit Price</th>
                            <th width="15%" class="amount">Tax</th>
                            <th width="15%" class="amount">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($estimate->items as $item)
                            <tr>
                                <td>
                                    <b>{{ $item->name }}</b>
                                    <div style="font-size: 11px; color: #888;">{{ $item->description }}</div>
                                </td>
                                <td class="center">{{ $item->quantity + 0 }}</td>
                                <td class="amount">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="amount">{{ $item->tax_1 + $item->tax_2 }}%</td>
                                <td class="amount">{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <table class="totals-table">
                <tr>
                    <td>Subtotal</td>
                    <td style="font-weight:bold">{{ $estimate->currency }} {{ number_format($estimate->subtotal, 2) }}
                    </td>
                </tr>
                <tr>
                    <td>Tax</td>
                    <td>{{ $estimate->currency }} {{ number_format($estimate->total_tax, 2) }}</td>
                </tr>
                <tr>
                    <td class="grand-total">Grand Total</td>
                    <td class="grand-total">{{ $estimate->currency }} {{ number_format($estimate->grand_total, 2) }}
                    </td>
                </tr>
            </table>

            <div class="about-section">
                <div class="about-title">Who We Are</div>
                <div class="about-text">{{ \App\Models\Setting::getCached('company_about') }}</div>
                <div class="about-text">{{ \App\Models\Setting::getCached('company_profile') }}</div>
            </div>

            @if($estimate->terms)
                <div style="margin-top: 30px;">
                    <div style="font-weight: bold; font-size: 12px; margin-bottom: 5px;">Terms & Conditions</div>
                    <div style="font-size: 11px; color: #666; white-space: pre-wrap;">{{ $estimate->terms }}</div>
                </div>
            @endif

            <div
                style="margin-top: 50px; text-align: center; color: #aaa; font-size: 10px; border-top: 1px solid #eee; padding-top: 15px;">
                {{ \App\Models\Setting::getCached('company_website') }}
            </div>
        </div>
    </div>
</body>

</html>