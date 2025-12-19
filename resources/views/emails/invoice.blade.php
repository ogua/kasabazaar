<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #dc2626;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .company-name .rose {
            color: #dc2626;
        }
        .content {
            padding: 20px 0;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 15px;
        }
        .details-box {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        .detail-value {
            color: #4169E1;
        }
        .total-box {
            background: #dc2626;
            color: white;
            border-radius: 8px;
            padding: 15px 20px;
            text-align: center;
            margin: 20px 0;
        }
        .total-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .total-amount {
            font-size: 28px;
            font-weight: bold;
            margin: 5px 0;
        }
        .tracking-box {
            background: #f0f9ff;
            border: 2px solid #4169E1;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
        }
        .tracking-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .tracking-number {
            font-size: 24px;
            font-weight: bold;
            color: #4169E1;
        }
        .message {
            font-size: 14px;
            color: #666;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #666;
            font-size: 12px;
        }
        .contact-info {
            margin-top: 15px;
        }
        .contact-info p {
            margin: 5px 0;
        }
        .btn {
            display: inline-block;
            background: #4169E1;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">
            <span class="rose">ROSE</span> DOOR TO DOOR SHIPPING AND DELIVERY CO
        </div>
    </div>

    <div class="content">
        <p class="greeting">Dear {{ $clientName }},</p>

        <p>Thank you for choosing Rose Door To Door Shipping and Delivery Co. Please find attached your shipping invoice for reference.</p>

        <div class="details-box">
            <div class="detail-row">
                <span class="detail-label">Invoice Reference:</span>
                <span class="detail-value">{{ $shipment->shipping_reference }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value">{{ $shipment->created_at->format('j M Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Shipping From:</span>
                <span class="detail-value">{{ $shipment->origin_branch_id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Shipping To:</span>
                <span class="detail-value">{{ $shipment->destination_branch_id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value">{{ ucfirst($shipment->status->value) }}</span>
            </div>
        </div>

        <div class="tracking-box">
            <div class="tracking-label">Your Tracking Number</div>
            <div class="tracking-number">{{ $shipment->tracking_number }}</div>
        </div>

        <div class="total-box">
            <div class="total-label">Total Amount</div>
            <div class="total-amount">${{ number_format($shipment->total, 2) }}</div>
            @php
                $paid = $shipment->payments->sum('amount');
                $balance = $shipment->total - $paid;
            @endphp
            @if($balance > 0)
            <div style="font-size: 14px; margin-top: 5px;">
                Paid: ${{ number_format($paid, 2) }} | Balance: ${{ number_format($balance, 2) }}
            </div>
            @else
            <div style="font-size: 14px; margin-top: 5px;">FULLY PAID</div>
            @endif
        </div>

        <p class="message">
            The detailed invoice is attached to this email as a PDF document. Please keep this for your records.
            If you have any questions about your shipment or invoice, please don't hesitate to contact us.
        </p>

        <p class="message">
            You can track your shipment using the tracking number provided above.
        </p>
    </div>

    <div class="footer">
        <p><strong>Thank you for your business!</strong></p>

        <div class="contact-info">
            <p><strong>Ghana Office:</strong> Adako Jachie, Ejisu - Kumasi</p>
            <p>+233 509725073 / +233 50 9725081</p>
            <p style="margin-top: 10px;"><strong>USA Office:</strong> Westfield, Indiana</p>
            <p>+1 (773) 970-0129 / +1 (574) 440-7460</p>
        </div>

        <p style="margin-top: 20px; color: #999;">
            This is an automated message. Please do not reply directly to this email.
        </p>
    </div>
</body>
</html>
