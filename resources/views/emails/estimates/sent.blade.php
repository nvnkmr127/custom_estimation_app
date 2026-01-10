@extends('emails.layout')

@section('content')
    <p>Hello {{ $notifiable_name }},</p>

    <p>We have prepared an estimate for your review.</p>

    <p>
        <strong>Estimate Number:</strong> #{{ $estimate_number }}<br>
        <strong>Total Amount:</strong> {{ $total_amount }}
    </p>

    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
        <tbody>
            <tr>
                <td align="left">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr>
                                <td> <a href="{{ $view_url }}" target="_blank">View & Sign Estimate</a> </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <p>If you have any questions, please don't hesitate to contact us.</p>

    <p>Thank you for your business!</p>

    <img src="{{ $pixel_url }}" width="1" height="1" style="display:none !important;" />
@endsection