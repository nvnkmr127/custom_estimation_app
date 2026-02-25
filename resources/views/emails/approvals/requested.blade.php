@extends('emails.layout')

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <div
            style="display: inline-block; background-color: #fef9c3; color: #854d0e; padding: 8px 16px; border-radius: 9999px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
            Action Required
        </div>
    </div>

    <h1>Approval Requested</h1>
    <p>Hello {{ $approver_name }},</p>
    <p>An estimate has been submitted and requires your review and approval before it can be sent to the client.</p>

    <div class="summary-box">
        <h3
            style="margin-top: 0; margin-bottom: 16px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
            Estimate Details</h3>
        <div class="summary-item">
            <span class="summary-label">Estimate Number</span>
            <span class="summary-value">#{{ $estimate_number }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Requested By</span>
            <span class="summary-value">{{ $requested_by }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Amount</span>
            <span class="summary-value" style="font-size: 18px; color: #2563eb;">{{ $total_amount }}</span>
        </div>
    </div>

    <div style="text-align: center; margin-top: 32px;">
        <a href="{{ $view_url }}" class="btn">Review & Approve</a>
    </div>

    <div class="divider"></div>

    <p style="font-size: 14px; color: #6b7280;">Please review the estimate details and any attached comments before taking
        action.</p>
@endsection