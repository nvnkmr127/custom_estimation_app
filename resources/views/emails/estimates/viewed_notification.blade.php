@extends('emails.layout')

@section('content')
    <h2>Estimate Viewed</h2>

    <p>Hello {{ $recipient_name }},</p>

    <p>Good news! Your estimate <strong>#{{ $estimate_number }}</strong> for <strong>{{ $client_name }}</strong> was just
        viewed.</p>

    <ul>
        <li><strong>Time:</strong> {{ $view_time }}</li>
        <li><strong>IP Address:</strong> {{ $ip_address }}</li>
    </ul>

    <p>
        <a href="{{ $view_url }}" class="button">View Details</a>
    </p>
@endsection