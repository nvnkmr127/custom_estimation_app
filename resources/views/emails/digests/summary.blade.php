@extends('emails.layout')

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <div
            style="display: inline-block; background-color: #f3f4f6; color: #4b5563; padding: 8px 16px; border-radius: 9999px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
            {{ str_replace('_', ' ', $frequency) }}
        </div>
    </div>

    <h1>Notification Summary</h1>
    <p>Hello {{ $recipient_name }},</p>
    <p>Here is your activity summary for {{ $date }}.</p>

    <div style="margin-top: 32px;">
        {{-- 1. Critical Highlights --}}
        @if($critical_items->isNotEmpty())
            <div
                style="margin-bottom: 32px; padding: 24px; background-color: #fef2f2; border-radius: 12px; border: 1px solid #fee2e2;">
                <h3 style="margin-top: 0; color: #991b1b; font-size: 16px; display: flex; align-items: center;">
                    <span style="margin-right: 8px;">🚨</span> Critical Alerts
                </h3>
                <div style="margin-top: 16px;">
                    @foreach($critical_items as $item)
                        <div
                            style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(153, 27, 27, 0.1); last-child: border-bottom-none;">
                            <div style="color: #b91c1c; font-size: 14px; font-weight: 600;">
                                {{ $item->payload['subject'] ?? 'Action Required' }}
                            </div>
                            @if(isset($item->payload['view_url']))
                                <a href="{{ $item->payload['view_url'] }}"
                                    style="color: #991b1b; font-size: 12px; text-decoration: underline; font-weight: 500;">View &
                                    Respond &rarr;</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 2. Summary Table --}}
        <div class="summary-box">
            <h3
                style="margin-top: 0; font-size: 14px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">
                Activity Summary</h3>
            @foreach($summaries as $summary)
                <div class="summary-item">
                    <span class="summary-label">{{ $summary['label'] }}</span>
                    <span class="summary-value" style="color: #2563eb;">{{ $summary['count'] }}</span>
                </div>
            @endforeach
        </div>

        {{-- 3. Grouped Details --}}
        <h3
            style="font-size: 16px; color: #111827; margin-top: 40px; margin-bottom: 20px; border-bottom: 1px solid #f3f4f6; padding-bottom: 12px;">
            Activity Details</h3>
        @foreach($grouped_notifications as $type => $group)
            <div style="margin-top: 24px;">
                <h4
                    style="font-size: 12px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; margin-bottom: 12px;">
                    {{ ucwords(str_replace(['.', '_'], ' ', $type)) }}
                </h4>
                @foreach($group as $notify)
                    <div
                        style="padding: 16px; background-color: #ffffff; border: 1px solid #f3f4f6; border-radius: 8px; margin-bottom: 12px;">
                        <p style="margin: 0; font-size: 14px; font-weight: 600; color: #374151;">
                            {{ $notify->payload['subject'] ?? 'Update' }}
                        </p>
                        @if(isset($notify->payload['view_url']))
                            <div style="margin-top: 8px;">
                                <a href="{{ $notify->payload['view_url'] }}"
                                    style="color: #3b82f6; text-decoration: none; font-size: 12px; font-weight: 500;">View Details
                                    &rarr;</a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="divider"></div>

    <p style="font-size: 12px; color: #6b7280;">
        You can manage your notification frequency and preferences in your <a href="{{ route('profile.edit') }}"
            style="color: #3b82f6;">profile settings</a>.
    </p>
@endsection