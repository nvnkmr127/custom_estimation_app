@extends('emails.layout')

@section('content')
    <p>Hello {{ $approver_name }},</p>

    <p>An estimate requires your approval.</p>

    <p>
        <strong>Estimate Number:</strong> #{{ $estimate_number }}<br>
        <strong>Total Amount:</strong> {{ $total_amount }}<br>
        <strong>Requested By:</strong> {{ $requested_by }}
    </p>

    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
        <tbody>
            <tr>
                <td align="left">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr>
                                <td> <a href="{{ $view_url }}" target="_blank">Review Estimate</a> </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
@endsection