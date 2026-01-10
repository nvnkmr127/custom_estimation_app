@extends('emails.layout')

@section('content')
    <h2>New Comment Notification</h2>

    <p>Hello {{ $recipient_name }},</p>

    <p>A new comment has been added to Estimate <strong>#{{ $estimate_number }}</strong> by {{ $comment_author }}.</p>

    <div style="background-color: #f3f4f6; padding: 15px; margin: 20px 0; border-left: 4px solid #3b82f6;">
        <p style="margin: 0; font-family: monospace;">"{{ $comment_content }}"</p>
    </div>

    <p>
        <a href="{{ $view_url }}" class="button">View Estimate & Reply</a>
    </p>

    <p>If the button above doesn't work, copy and paste this link into your browser:<br>
        <small>{{ $view_url }}</small>
    </p>
@endsection