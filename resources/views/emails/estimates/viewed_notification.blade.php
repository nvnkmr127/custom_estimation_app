@extends('emails.layout')

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <div
            style="display: inline-block; background-color: #dcfce7; color: #166534; padding: 8px 16px; border-radius: 9999px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
            Notification
        </div>
    </div>

    <h1>Estimate Viewed</h1>
    <p>Hello {{ $recipient_name }},</p>
    <p>Good news! Your estimate <strong>#{{ $estimate_number }}</strong> for <strong>{{ $client_name }}</strong> was just
        viewed by the client.</p>

    <div class="summary-box">
        <h3
            style="margin-top: 0; margin-bottom: 16px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
            View Details</h3>
        <div class="summary-item">
            <span class="summary-label">Time</span>
            <span class="summary-value">{{ $view_time }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">IP Address</span>
            <span class="summary-value">{{ $ip_address }}</span>
        </div>
    </div>

    <div style="text-align: center; margin-top: 32px;">
        <a href="{{ $view_url }}" class="btn">View Estimate Details</a>
    </div>

    <div class="divider"></div>

    <p style="font-size: 14px; color: #6b7280;">Tracking these interactions helps you follow up at the perfect moment.</p>
@endsection