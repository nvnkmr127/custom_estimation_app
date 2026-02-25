@extends('emails.layout')

@section('content')
    <h1>Estimate #{{ $estimate_number }} is Ready</h1>
    <p>Hello {{ $notifiable_name }},</p>
    <p>We've prepared an estimate for your review. Please find the summary below and click the button to view the full
        details and sign the document.</p>

    <div class="summary-box">
        <h3
            style="margin-top: 0; margin-bottom: 16px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
            Estimate Summary</h3>
        <div class="summary-item">
            <span class="summary-label">Estimate Number</span>
            <span class="summary-value">#{{ $estimate_number }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Amount</span>
            <span class="summary-value" style="font-size: 18px; color: #2563eb;">{{ $total_amount }}</span>
        </div>
    </div>

    <div style="text-align: center; margin-top: 32px; margin-bottom: 32px;">
        <a href="{{ $view_url }}" class="btn" style="padding: 16px 32px; font-size: 16px;">View & Sign Estimate</a>
    </div>

    <div class="divider"></div>

    <p style="font-size: 14px; color: #6b7280;">If you have any questions or require modifications to this estimate, please
        feel free to reach out to us by replying to this email.</p>
    <p>Thank you for your business!</p>

    <img src="{{ $pixel_url }}" width="1" height="1" style="display:none !important;" />
@endsection