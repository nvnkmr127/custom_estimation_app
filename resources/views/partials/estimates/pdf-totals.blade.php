<table style="width: 40%; margin-left: auto; margin-top: 20px; border-collapse: collapse; font-family: inherit;">
    <tr>
        <td style="padding: 5px; text-align: left; color: #64748b;">Subtotal</td>
        <td style="padding: 5px; text-align: right; font-weight: bold; color: #1e293b;">
            {{ $estimate->currency }} {{ number_format($estimate->subtotal, 2) }}
        </td>
    </tr>
    @if($estimate->has_discount)
        <tr>
            <td style="padding: 5px; text-align: left; color: #ef4444;">Discount</td>
            <td style="padding: 5px; text-align: right; font-weight: bold; color: #ef4444;">
                - {{ $estimate->currency }} {{ number_format($estimate->discount_total, 2) }}
            </td>
        </tr>
    @endif
    @if($estimate->has_tax)
        <tr>
            <td style="padding: 5px; text-align: left; color: #64748b;">Tax</td>
            <td style="padding: 5px; text-align: right; font-weight: bold; color: #1e293b;">
                {{ $estimate->currency }} {{ number_format($estimate->total_tax, 2) }}
            </td>
        </tr>
    @endif
    @if($estimate->has_transportation)
        <tr>
            <td style="padding: 5px; text-align: left; color: #64748b;">Transportation</td>
            <td style="padding: 5px; text-align: right; font-weight: bold; color: #1e293b;">
                {{ $estimate->currency }} {{ number_format($estimate->transportation_charges, 2) }}
            </td>
        </tr>
    @endif
    <tr>
        <td
            style="padding: 10px 5px; text-align: left; font-weight: bold; font-size: 1.1em; border-top: 1px solid #e2e8f0; color: #1e293b;">
            Grand Total
        </td>
        <td
            style="padding: 10px 5px; text-align: right; font-weight: bold; font-size: 1.1em; border-top: 1px solid #e2e8f0; color: #1e293b;">
            {{ $estimate->currency }} {{ number_format($estimate->grand_total, 2) }}
        </td>
    </tr>
</table>