<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Approved</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; border-bottom: 3px solid #dc2626; padding-bottom: 20px; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: bold; color: #333; }
        .company-name .rose { color: #dc2626; }
        .approved-banner { background: #f0fdf4; border: 2px solid #16a34a; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center; }
        .approved-icon { font-size: 36px; margin-bottom: 8px; }
        .approved-title { font-size: 22px; font-weight: bold; color: #16a34a; margin: 0 0 6px; }
        .approved-sub { font-size: 14px; color: #666; margin: 0; }
        .greeting { font-size: 16px; margin-bottom: 15px; }
        .details-box { background: #f3f4f6; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: bold; color: #666; }
        .detail-value { color: #333; }
        .ref-box { background: #f0f9ff; border: 2px solid #4169E1; border-radius: 8px; padding: 15px; text-align: center; margin: 20px 0; }
        .ref-label { font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 1px; }
        .ref-number { font-size: 22px; font-weight: bold; color: #4169E1; }
        .total-box { background: #dc2626; color: white; border-radius: 8px; padding: 15px 20px; text-align: center; margin: 20px 0; }
        .total-label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .total-amount { font-size: 28px; font-weight: bold; margin: 5px 0; }
        .pay-btn { display: inline-block; background: #dc2626; color: white !important; padding: 16px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 17px; margin: 8px 0; letter-spacing: 0.5px; }
        .message { font-size: 14px; color: #666; margin: 20px 0; }
        .footer { text-align: center; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #666; font-size: 12px; }
        .contact-info p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name"><span class="rose">ROSE</span> DOOR TO DOOR SHIPPING AND DELIVERY CO</div>
    </div>

    <div class="approved-banner">
        <div class="approved-icon">✅</div>
        <p class="approved-title">Your Shipment Has Been Approved!</p>
        <p class="approved-sub">Your shipment request has been reviewed and approved by our team.</p>
    </div>

    <div class="content">
        <p class="greeting">Dear {{ $client->name }},</p>

        <p>Great news! Your shipment request has been approved and a shipment has been created for you. Please find your shipment details below and your invoice attached to this email.</p>

        <div class="ref-box">
            <div class="ref-label">Shipment Reference</div>
            <div class="ref-number">{{ $shipment->shipping_reference }}</div>
            @if($shipment->tracking_number)
            <div style="font-size: 13px; color: #666; margin-top: 6px;">Tracking: {{ $shipment->tracking_number }}</div>
            @endif
        </div>

        <div class="details-box">
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value">{{ ucfirst($shipment->status->value ?? $shipment->status) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date Approved:</span>
                <span class="detail-value">{{ now()->format('j M Y') }}</span>
            </div>
            @php
                $total = $shipment->total ?? 0;
                $paid  = $shipment->payments?->sum('amount') ?? 0;
                $balance = $total - $paid;
            @endphp
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value">USD {{ number_format($total, 2) }}</span>
            </div>
            @if($balance > 0)
            <div class="detail-row">
                <span class="detail-label">Balance Due:</span>
                <span class="detail-value" style="color: #dc2626; font-weight: bold;">USD {{ number_format($balance, 2) }}</span>
            </div>
            @endif
        </div>

        @if($balance > 0)
        <div style="text-align: center; margin: 28px 0;">
            <p style="font-size: 15px; font-weight: bold; margin-bottom: 12px;">Ready to pay? Click below to complete your payment securely:</p>
            <a href="{{ $paymentUrl }}" class="pay-btn">Make Payment Now</a>
            <p style="font-size: 12px; color: #999; margin-top: 10px;">Powered by Paystack — secure payment processing</p>
        </div>
        @endif

        <p class="message">
            Your detailed invoice is attached to this email as a PDF document. Please keep this for your records.
        </p>

        <p class="message">
            You can track your shipment using the reference number above. If you have any questions, please contact us.
        </p>
    </div>

    <div class="footer">
        <p><strong>Thank you for choosing Rose Door To Door!</strong></p>
        <div class="contact-info">
            <p><strong>Ghana Office:</strong> Adako Jachie, Ejisu - Kumasi</p>
            <p>+233 509725073 / +233 50 9725081</p>
            <p style="margin-top: 10px;"><strong>USA Office:</strong> Westfield, Indiana</p>
            <p>+1 (773) 970-0129 / +1 (574) 440-7460</p>
        </div>
        <p style="margin-top: 20px; color: #999;">This is an automated message. Please do not reply directly to this email.</p>
    </div>
</body>
</html>
