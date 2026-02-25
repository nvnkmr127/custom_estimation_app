@extends('emails.layout')

@section('content')
    <h1>Welcome, {{ $name }}!</h1>
    <p>We're absolutely thrilled to have you join {{ config('app.name') }}. Your account is all set and ready for you to
        start exploring.</p>
    <p>Our platform is designed to help you streamline your estimation process and win more business with beautiful,
        professional proposals.</p>

    <div style="text-align: center;">
        <a href="{{ url('/') }}" class="btn">Go to Dashboard</a>
    </div>

    <div class="divider"></div>

    <p style="font-size: 14px; color: #6b7280;">If you have any questions or need a hand getting started, simply reply to
        this email. Our team is always here to help!</p>
    <p>Best regards,<br>The {{ config('app.name') }} Team</p>
@endsection