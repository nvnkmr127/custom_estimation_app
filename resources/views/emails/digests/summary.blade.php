@extends('emails.layout')

@section('content')
    <h2>Notification Summary</h2>
    <p>Hello {{ $recipient_name }},</p>
    <p>Here is your {{ str_replace('_', ' ', $frequency) }} for {{ $date }}.</p>

    <div style="margin-top: 20px;">
        {{-- 1. Critical Highlights --}}
        @if($critical_items->isNotEmpty())
            <div
                style="margin-bottom: 25px; padding: 15px; background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #991b1b; font-size: 16px;">🚨 Critical Alerts</h3>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    @foreach($critical_items as $item)
                        <li style="margin-bottom: 8px; color: #b91c1c; font-size: 13px;">
                            <strong>{{ $item->payload['subject'] ?? 'Action Required' }}</strong>
                            @if(isset($item->payload['view_url']))
                                - <a href="{{ $item->payload['view_url'] }}"
                                    style="color: #991b1b; text-decoration: underline;">View</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 2. Summary Table --}}
        <div style="margin-bottom: 25px; background-color: #f9fafb; padding: 15px; border-radius: 8px;">
            <h3 style="margin-top: 0; font-size: 14px; color: #374151;">Activity Summary</h3>
            <table style="width: 100%; font-size: 13px; color: #4b5563;">
                @foreach($summaries as $summary)
                    <tr>
                        <td style="padding: 4px 0;">{{ $summary['label'] }}</td>
                        <td style="text-align: right; font-weight: bold; color: #2563eb;">{{ $summary['count'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

        {{-- 3. Grouped Details --}}
        <h3 style="font-size: 14px; color: #374151; border-bottom: 2px solid #f3f4f6; padding-bottom: 5px;">Activity Details
        </h3>
        @foreach($grouped_notifications as $type => $group)
            <div style="margin-top: 15px;">
                <h4 style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                    {{ ucwords(str_replace(['.', '_'], ' ', $type)) }}
                </h4>
                @foreach($group as $notify)
                    <div style="padding: 12px; border-left: 3px solid #e5e7eb; margin-bottom: 8px;">
                        <p style="margin: 0; font-size: 13px; font-weight: 500;">
                            {{ $notify->payload['subject'] ?? 'Update' }}
                        </p>
                        @if(isset($notify->payload['view_url']))
                            <a href="{{ $notify->payload['view_url'] }}"
                                style="color: #2563eb; text-decoration: none; font-size: 11px;">View Details &rarr;</a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <p style="margin-top: 30px; font-size: 12px; color: #6b7280;">
        You can change your notification preferences in your <a href="{{ route('profile.edit') }}">profile settings</a>.
    </p>
@endsection