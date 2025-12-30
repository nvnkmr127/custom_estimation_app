<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate #{{ $estimate->estimate_number }}</title>
    <style>
        @page { margin: 0; size: A4; }
        body { font-family: 'Times New Roman', Times, serif; color: #000; line-height: 1.4; margin: 0; padding: 0; background: #fff; font-size: 13px; }
        .page-container { width: 210mm; min-height: 297mm; padding: 25mm 20mm; margin: 0 auto; box-sizing: border-box; }
        @media print { 
            body { background: none; }
            .page-container { width: 100%; height: auto; padding: 0; margin: 0; box-shadow: none; }
            .no-print { display: none !important; }
        }
        
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
        .company-name { font-size: 20px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
        .doc-title { font-size: 24px; font-weight: bold; margin: 20px 0; text-align: center; text-decoration: underline; }
        
        .meta-table { width: 100%; margin-bottom: 20px; }
        .meta-table td { vertical-align: top; padding: 5px; }
        .label { font-weight: bold; width: 100px; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid #000; }
        .items-table th { border: 1px solid #000; padding: 8px; text-align: center; background: #eee; font-weight: bold; }
        .items-table td { border: 1px solid #000; padding: 8px; }
        .items-table .amount { text-align: right; }
        .items-table .center { text-align: center; }
        
        .totals-table { width: 40%; float: right; margin-top: 20px; border-collapse: collapse; }
        .totals-table td { padding: 5px; border-bottom: 1px solid #000; }
        .grand-total { font-weight: bold; font-size: 14px; border-bottom: 3px double #000 !important; }
        
        .terms { clear: both; margin-top: 50px; font-style: italic; border-top: 1px solid #000; padding-top: 10px; }
        
        .print-btn { position: fixed; bottom: 30px; right: 30px; padding: 10px 20px; background: #000; color: #fff; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-btn no-print">Print Estimate</button>

    <div class="page-container">
        <div class="header">
            <div class="company-name">{{ $settings['company_legal_name'] ?? ($settings['app_name'] ?? config('app.name')) }}</div>
            <div>{{ $settings['company_address_street'] }} {{ $settings['company_address_city'] }}</div>
            <div>{{ $settings['company_email'] }} | {{ $settings['company_phone'] }}</div>
        </div>

        <div class="doc-title">ESTIMATE</div>

        <table class="meta-table">
            <tr>
                <td width="50%">
                    <div style="border: 1px solid #000; padding: 10px;">
                        <strong>Bill To:</strong><br>
                        Client ID: {{ $estimate->client_id }}
                    </div>
                </td>
                <td width="50%" align="right">
                    <table>
                        <tr><td class="label">Estimate #:</td><td>{{ $estimate->estimate_number }}</td></tr>
                        <tr><td class="label">Date:</td><td>{{ \Carbon\Carbon::parse($estimate->estimate_date)->format('d/m/Y') }}</td></tr>
                        <tr><td class="label">Expiry:</td><td>{{ $estimate->expiry_date ? \Carbon\Carbon::parse($estimate->expiry_date)->format('d/m/Y') : '-' }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estimate->items as $item)
                    <tr>
                        <td>
                            <b>{{ $item->name }}</b>
                            @if($item->description)<br><small>{{ $item->description }}</small>@endif
                        </td>
                        <td class="center">{{ $item->quantity + 0 }} {{ $item->unit_type }}</td>
                        <td class="amount">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="amount">{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals-table">
            <tr><td>Subtotal</td><td align="right">{{ number_format($estimate->subtotal, 2) }}</td></tr>
            @if($estimate->total_tax > 0)
                <tr><td>Tax</td><td align="right">{{ number_format($estimate->total_tax, 2) }}</td></tr>
            @endif
            @if($estimate->discount_total > 0)
                <tr><td>Discount</td><td align="right">- {{ number_format($estimate->discount_total, 2) }}</td></tr>
            @endif
            <tr class="grand-total"><td>Total</td><td align="right">{{ $estimate->currency }} {{ number_format($estimate->grand_total, 2) }}</td></tr>
        </table>

        @if($estimate->terms)
            <div class="terms">
                <strong>Terms & Conditions:</strong><br>
                {{ $estimate->terms }}
            </div>
        @endif
    </div>
</body>
</html>
