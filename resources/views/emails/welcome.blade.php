@extends('emails.layout')

@section('content')
    <p>Hello {{ $name }},</p>
    <p>Welcome to {{ config('app.name') }}! We are excited to have you on board.</p>
    <p>If you have any questions, feel free to reply to this email.</p>
    <p>Best regards,</p>
    <p>The {{ config('app.name') }} Team</p>
@endsection