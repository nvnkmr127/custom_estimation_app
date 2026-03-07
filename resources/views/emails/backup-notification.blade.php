<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .card {
            max-width: 560px;
            margin: 0 auto;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
        }

        .header {
            background: linear-gradient(135deg, #1d4ed8, #4f46e5);
            padding: 32px 24px;
            color: #fff;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
        }

        .header p {
            margin: 8px 0 0;
            opacity: .85;
            font-size: 14px;
        }

        .body {
            padding: 28px 24px;
        }

        .meta {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .meta table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            padding: 6px 0;
            font-size: 14px;
            color: #374151;
        }

        .meta td:first-child {
            font-weight: 600;
            color: #6b7280;
            width: 40%;
        }

        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            background: #dcfce7;
            color: #166534;
        }

        .footer {
            padding: 16px 24px;
            background: #f8fafc;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }

        a {
            color: #4f46e5;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="header">
            <h1>✅ Backup Completed</h1>
            <p>{{ $appName }} — Automated Backup Notification</p>
        </div>
        <div class="body">
            <p style="color:#374151;font-size:15px;margin-top:0;">Your application backup has completed successfully.
            </p>
            <div class="meta">
                <table>
                    <tr>
                        <td>Filename</td>
                        <td>{{ $log->filename }}</td>
                    </tr>
                    <tr>
                        <td>Size</td>
                        <td>{{ $log->formatted_size }}</td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td><span class="badge">{{ ucfirst($log->status) }}</span></td>
                    </tr>
                    <tr>
                        <td>Triggered By</td>
                        <td>{{ ucfirst($log->triggered_by) }}</td>
                    </tr>
                    <tr>
                        <td>Completed At</td>
                        <td>{{ $log->completed_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                    </tr>
                    @if($log->google_drive_url)
                        <tr>
                            <td>Drive Link</td>
                            <td><a href="{{ $log->google_drive_url }}">View on Google Drive</a></td>
                        </tr>
                    @endif
                </table>
            </div>
            <p style="font-size:13px;color:#6b7280;">Log in to your admin panel to manage or download this backup.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ $appName }}. This is an automated message.
        </div>
    </div>
</body>

</html>