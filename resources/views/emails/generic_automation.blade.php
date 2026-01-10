<!DOCTYPE html>
<html>

<head>
    <title>Automation Notification</title>
</head>

<body>
    <h1>Automation Notification</h1>
    <p>This message was automatically triggered by a rule in our system.</p>

    @if(isset($message))
        <p>{{ $message }}</p>
    @endif

    <hr>
    <p>Details:</p>
    <ul>
        @foreach($payload as $key => $value)
            @if(is_string($value) || is_numeric($value))
                <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</li>
            @endif
        @endforeach
    </ul>
</body>

</html>