@extends('emails.layout')

@section('content')
    <h1>Automation Notification</h1>
    <p>This message was automatically triggered by an automation rule in our system.</p>

    @if(isset($message))
        <div
            style="background-color: #f3f4f6; padding: 16px; border-radius: 8px; margin-bottom: 24px; font-style: italic; color: #4b5563;">
            "{{ $message }}"
        </div>
    @endif

    <div class="summary-box">
        <h3
            style="margin-top: 0; margin-bottom: 16px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
            Rule Details</h3>
        @foreach($payload as $key => $value)
            @if(is_string($value) || is_numeric($value))
                <div class="summary-item">
                    <span class="summary-label">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                    <span class="summary-value">{{ $value }}</span>
                </div>
            @endif
        @endforeach
    </div>

    <div class="divider"></div>

    <p style="font-size: 14px; color: #6b7280;">You can manage your automation rules in the settings dashboard.</p>
@endsection