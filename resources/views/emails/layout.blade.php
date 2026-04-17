<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{{ $subject ?? config('app.name') }}</title>
    <!--[if !mso]><!-->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!--<![endif]-->
    <style>
        @media only screen and (max-width: 620px) {
            .container {
                width: 100% !important;
                padding: 10px !important;
            }

            .content-box {
                padding: 24px !important;
                border-radius: 0 !important;
            }

            .header {
                padding: 20px 0 !important;
            }
        }

        body {
            background-color: #f8fafc;
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            font-size: 16px;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #334155;
        }

        .wrapper {
            background-color: #f8fafc;
            width: 100%;
            padding: 40px 0;
        }

        .container {
            margin: 0 auto !important;
            max-width: 600px;
            width: 600px;
        }

        .header {
            padding: 0 0 40px 0;
            text-align: center;
        }

        .logo {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            text-decoration: none;
            letter-spacing: -1px;
            text-transform: uppercase;
        }

        .content-box {
            background: #ffffff;
            border-radius: 20px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            padding: 48px;
            box-sizing: border-box;
            border: 1px solid #f1f5f9;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            width: 100%;
            padding: 0 20px;
        }

        .footer p {
            color: #94a3b8;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .footer a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .btn {
            display: inline-block;
            background: #2563eb linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff !important;
            padding: 16px 32px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 10px;
            margin-top: 24px;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
            transition: transform 0.2s;
        }

        h1 {
            font-size: 26px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }

        p {
            margin-top: 0;
            margin-bottom: 20px;
            color: #475569;
        }

        .card {
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
            border: 1px solid #f1f5f9;
        }

        .card-title {
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .card-row {
            margin-bottom: 12px;
            display: block;
            overflow: auto;
        }

        .card-label {
            color: #64748b;
            font-weight: 500;
            font-size: 14px;
            float: left;
        }

        .card-value {
            color: #0f172a;
            font-weight: 700;
            font-size: 14px;
            float: right;
        }

        .divider {
            height: 1px;
            background-color: #f1f5f9;
            margin: 32px 0;
        }

        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .btn-secondary {
            display: inline-block;
            background-color: #ffffff;
            color: #475569 !important;
            padding: 12px 24px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 10px;
            margin-top: 24px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-sm { font-size: 14px; }
        .text-xs { font-size: 12px; }
        .font-bold { font-weight: 700; }
        
        .mt-0 { margin-top: 0; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }

        .price-text {
            color: #2563eb;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .item-list {
            width: 100%;
            border-top: 1px solid #f1f5f9;
            margin-top: 24px;
        }

        .item-row {
            border-bottom: 1px solid #f1f5f9;
            padding: 12px 0;
            overflow: auto;
        }

        .item-name {
            font-weight: 600;
            color: #0f172a;
            display: block;
        }

        .item-desc {
            font-size: 13px;
            color: #64748b;
            display: block;
            margin-top: 4px;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <a href="{{ config('app.url') }}" class="logo">
                   {{ config('app.name') }}
                </a>
            </div>

            <div class="content-box">
                @if(isset($priority_badge))
                    {!! $priority_badge !!}
                @endif
                
                @yield('content')
            </div>

            <div class="footer">
                @php
                    $settings = \App\Models\Setting::getAllCached();
                @endphp
                <p>&copy; {{ date('Y') }} {{ $settings['company_legal_name'] ?? config('app.name') }}. All rights reserved.</p>
                <p>
                    {{ $settings['company_address_street'] ?? '' }}
                    @if(isset($settings['company_address_city'])) , {{ $settings['company_address_city'] }} @endif
                </p>
                <p>
                    <a href="{{ config('app.url') }}/notifications">Manage Notification Preferences</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>