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
            font-family: 'Inter', sans-serif;
            color: #444;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 14px;
        }

        .page-container {
            width: 210mm;
            min-height: 297mm;
            padding: 40px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        @media print {
            body {
                background: none;
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

        h1 {
            font-weight: 900;
            letter-spacing: -1px;
            margin: 0 0 10px 0;
            color: #000;
        }

        .meta-text {
            color: #888;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .header {
            margin-bottom: 60px;
        }

        .client-section {
            margin-bottom: 60px;
            padding-left: 20px;
            border-left: 4px solid #000;
        }

        .items-header {
            display: flex;
            font-size: 11px;
            text-transform: uppercase;
            color: #888;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .item-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #f9f9f9;
        }

        .col-desc {
            flex: 3;
        }

        .col-num {
            flex: 1;
            text-align: right;
        }

        .totals {
            margin-top: 40px;
            float: right;
            width: 300px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .grand-total {
            font-weight: 900;
            font-size: 20px;
            color: #000;
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }

        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px;
            background: #000;
            color: #fff;
            border-radius: 50%;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <button onclick="window.print()" class="print-btn no-print"><svg style="width:24px;height:24px" viewBox="0 0 24 24">
            <path fill="currentColor"
                d="M19,8H5C3.34,8 2,9.34 2,11V17H6V21H18V17H22V11C22,9.34 20.66,8 19,8M16,19H8V14H16V19M19,12C18.45,12 18,11.55 18,11C18,10.45 18.45,10 19,10C19.55,10 20,10.45 20,11C20,11.55 19.55,12 19,12M6,1.5C6,0.67 6.67,0 7.5,0H16.5C17.33,0 18,0.67 18,1.5V1.5C18,2.33 17.33,3 16.5,3H7.5C6.67,3 6,2.33 6,1.5V1.5Z" />
        </svg></button>

    <div class="page-container">
        <div class="header">
            @if(isset($settings['app_logo']))
                <img src="{{ asset($settings['app_logo']) }}" style="height: 40px; margin-bottom: 20px;">
            @endif
            <h1>Estimate {{ $estimate->estimate_number }}</h1>
            <div class="meta-text">{{ \Carbon\Carbon::parse($estimate->estimate_date)->format('F j, Y') }}</div>
        </div>

        <div class="client-section">
            <div class="meta-text">PREPARED FOR</div>
            <div style="font-weight: bold; font-size: 18px;">Client #{{ $estimate->client_id }}</div>
            <!-- Client Name would go here -->
        </div>

        <div class="items-header">
            <div class="col-desc">Description</div>
            <div class="col-num">Qty</div>
            <div class="col-num">Price</div>
            <div class="col-num">Total</div>
        </div>

        @foreach($estimate->items as $item)
            <div class="item-row">
                <div class="col-desc">
                    <b>{{ $item->name }}</b>
                </div>
                <div class="col-num">{{ $item->quantity + 0 }}</div>
                <div class="col-num">{{ number_format($item->unit_price, 2) }}</div>
                <div class="col-num font-bold">{{ number_format($item->total, 2) }}</div>
            </div>
        @endforeach

        <div class="totals">
            <div class="total-row"><span>Subtotal</span><span>{{ number_format($estimate->subtotal, 2) }}</span></div>
            @if($estimate->total_tax > 0)
                <div class="total-row"><span>Tax</span><span>{{ number_format($estimate->total_tax, 2) }}</span></div>
            @endif
            @if($estimate->discount_total > 0)
                <div class="total-row"><span>Discount</span><span>{{ number_format($estimate->discount_total, 2) }}</span>
                </div>
            @endif
            <div class="total-row grand-total"><span>Total</span><span>{{ $estimate->currency }}
                    {{ number_format($estimate->grand_total, 2) }}</span></div>
        </div>
    </div>
</body>

</html>