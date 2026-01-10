@extends('emails.layout')

@section('content')
    <h2>Notification Summary</h2>
    <p>Hello {{ $recipient_name }},</p>
    <p>Here is your {{ str_replace('_', ' ', $frequency) }} for {{ $date }}.</p>

    <div style="margin-top: 20px;">
        @foreach($notifications as $notify)
            <div style="padding: 15px; border-bottom: 1px solid #e5e7eb; margin-bottom: 10px;">
                <h3 style="margin: 0; color: #3b82f6; font-size: 14px;">
                    {{ $notify->payload['subject'] ?? 'Notification' }}
                </h3>
                <p style="margin: 5px 0 0 0; font-size: 13px; color: #4b5563;">
                    Type: <strong>{{ str_replace('.', ' ', $notify->event_type) }}</strong>
                    <span style="color: #9ca3af; margin-left: 10px;">{{ $notify->created_at->format('h:i A') }}</span>
                </p>

                @if(isset($notify->payload['view_url']))
                    <p style="margin-top: 10px;">
                        <a href="{{ $notify->payload['view_url'] }}"
                            style="color: #2563eb; text-decoration: underline; font-size: 12px;">View Details</a>
                    </p>
                @endif
            </div>
        @endforeach
    </div>

    <p style="margin-top: 30px; font-size: 12px; color: #6b7280;">
        You can change your notification preferences in your <a href="{{ route('profile.edit') }}">profile settings</a>.
    </p>
@endsection