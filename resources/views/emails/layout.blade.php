<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <!--[if !mso]><!-->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!--<![endif]-->
    <style>
        @media only screen and (max-width: 620px) {
            .container {
                width: 100% !important;
                padding: 10px !important;
            }

            .content-box {
                padding: 20px !important;
            }
        }

        body {
            background-color: #f3f4f6;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            font-size: 16px;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
            color: #1f2937;
        }

        table {
            border-collapse: separate;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            width: 100%;
        }

        table td {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 16px;
            vertical-align: top;
        }

        .wrapper {
            background-color: #f3f4f6;
            width: 100%;
            padding: 40px 0;
        }

        .container {
            display: block;
            margin: 0 auto !important;
            max-width: 600px;
            width: 600px;
        }

        .header {
            padding: 0 0 30px 0;
            text-align: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            text-decoration: none;
            letter-spacing: -0.025em;
        }

        .content-box {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 40px;
            box-sizing: border-box;
        }

        .footer {
            clear: both;
            margin-top: 30px;
            text-align: center;
            width: 100%;
            padding: 0 20px;
        }

        .footer p {
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }

        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            padding: 12px 24px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
        }

        .btn:hover {
            background-color: #1d4ed8;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin-top: 0;
            margin-bottom: 20px;
            letter-spacing: -0.025em;
        }

        p {
            margin-top: 0;
            margin-bottom: 16px;
        }

        .summary-box {
            background-color: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            border: 1px solid #f3f4f6;
        }

        .summary-item {
            margin-bottom: 8px;
            font-size: 14px;
        }

        .summary-label {
            color: #6b7280;
            font-weight: 500;
        }

        .summary-value {
            color: #111827;
            font-weight: 600;
            float: right;
        }

        .divider {
            height: 1px;
            background-color: #f3f4f6;
            margin: 24px 0;
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
                @yield('content')
            </div>

            <div class="footer">
                @php
                    $settings = \App\Models\Setting::getAllCached();
                @endphp
                <p>&copy; {{ date('Y') }} {{ $settings['company_legal_name'] ?? config('app.name') }}. All rights
                    reserved.</p>
                <p>
                    {{ $settings['company_address_street'] ?? '' }}
                    {{ isset($settings['company_address_city']) ? ', ' . $settings['company_address_city'] : '' }}
                    {{ isset($settings['company_address_state']) ? ', ' . $settings['company_address_state'] : '' }}
                </p>
                <p>
                    You received this email because it's related to your activity in {{ config('app.name') }}.
                </p>
            </div>
        </div>
    </div>
</body>

</html>