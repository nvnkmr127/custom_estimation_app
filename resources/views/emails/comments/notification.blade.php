@extends('emails.layout')

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <div
            style="display: inline-block; background-color: #dbeafe; color: #1e40af; padding: 8px 16px; border-radius: 9999px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
            New Comment
        </div>
    </div>

    <h1>New Comment on #{{ $estimate_number }}</h1>
    <p>Hello {{ $recipient_name }},</p>
    <p><strong>{{ $comment_author }}</strong> just left a comment on Estimate #{{ $estimate_number }}.</p>

    <div
        style="background-color: #f9fafb; padding: 24px; border-radius: 12px; margin: 24px 0; border: 1px solid #f3f4f6; position: relative;">
        <div
            style="color: #6b7280; font-size: 48px; position: absolute; top: 0; left: 16px; opacity: 0.1; font-family: serif; line-height: 1;">
            &ldquo;</div>
        <p style="margin: 0; position: relative; z-index: 1; font-style: italic; color: #374151; padding: 0 8px;">
            {{ $comment_content }}</p>
    </div>

    <div style="text-align: center; margin-top: 32px;">
        <a href="{{ $view_url }}" class="btn">View & Reply</a>
    </div>

    <div class="divider"></div>

    <p style="font-size: 14px; color: #6b7280;">You can also view the full history of comments in the estimate portal.</p>
@endsection