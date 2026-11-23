@php
    $companyName = config('group.parent.name', 'KASAROSE LOGISTICS');
    $contact = config('website.contact');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt {{ $receipt->receipt_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; border-bottom: 3px solid #A0043C; padding-bottom: 20px; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: bold; color: #333; }
        .company-name .rose { color: #A0043C; }
        .details-box { background: #f3f4f6; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .details-box table { width: 100%; border-collapse: collapse; }
        .details-box td { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .details-box tr:last-child td { border-bottom: none; }
        .label { font-weight: bold; color: #666; }
        .value { color: #003151; font-weight: 500; text-align: right; }
        .btn { display: inline-block; padding: 13px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 14px; background: #003151; color: #fff !important; }
        .footer { text-align: center; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name"><span class="rose">KASAROSE</span> LOGISTICS</div>
    </div>

    <p>Dear {{ $receipt->client?->name }},</p>
    <p>As requested, please find attached your receipt for the payment you made to {{ $companyName }}.</p>

    <div class="details-box">
        <table>
            <tr><td class="label">Receipt No</td><td class="value">{{ $receipt->receipt_number }}</td></tr>
            <tr><td class="label">Amount Paid</td><td class="value">{{ $receipt->currency }} {{ number_format((float) $receipt->amount, 2) }}</td></tr>
            <tr><td class="label">Date Paid</td><td class="value">{{ $receipt->paid_on->format('j M Y') }}</td></tr>
            <tr><td class="label">Payment Method</td><td class="value">{{ $receipt->payment_method }}</td></tr>
            <tr><td class="label">For</td><td class="value">{{ $receipt->description }}</td></tr>
            @if ($receipt->related_reference)
                <tr><td class="label">Shipment / Invoice</td><td class="value">{{ $receipt->related_reference }}</td></tr>
            @endif
        </table>
    </div>

    <p style="text-align: center; margin: 24px 0;">
        <a href="{{ $viewUrl }}" class="btn">View Receipt Online</a>
    </p>

    <p style="font-size: 13px; color: #666; text-align: center;">The receipt is also attached to this email as a PDF. Please keep it for your records.</p>

    <div class="footer">
        <p><strong>Thank you for choosing {{ $companyName }}!</strong></p>
        <p>Ghana: {{ $contact['address_gh'] ?? '' }} &middot; {{ $contact['phone_gh_1'] ?? '' }}</p>
        <p>USA: {{ $contact['phone_us_1'] ?? '' }} &middot; {{ $contact['email_support'] ?? '' }}</p>
    </div>
</body>
</html>
