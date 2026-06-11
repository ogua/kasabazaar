<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmed</title>
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
            border-bottom: 3px solid #A0043C;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .company-name .rose { color: #A0043C; }
        .banner {
            background: #16a34a;
            color: white;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            margin: 20px 0;
        }
        .banner-icon  { font-size: 36px; }
        .banner-title { font-size: 20px; font-weight: bold; margin-top: 8px; }
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
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: bold; color: #666; }
        .detail-value { color: #003151; font-weight: 500; }
        .cta-row {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin: 24px 0;
        }
        .btn {
            display: inline-block;
            padding: 13px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            text-align: center;
        }
        .btn-invoice { background: #A0043C; color: white; }
        .btn-receipt { background: #003151; color: white; }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #666;
            font-size: 12px;
        }
        .contact-info p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">
            <span class="rose">ROSE</span> DOOR TO DOOR SHIPPING AND DELIVERY CO
        </div>
    </div>

    <div class="banner">
        <div class="banner-icon">✅</div>
        <div class="banner-title">Payment Confirmed!</div>
    </div>

    <p>Dear {{ $clientName }},</p>
    <p>We have received your payment. Here are the details:</p>

    <div class="details-box">
        <div class="detail-row">
            <span class="detail-label">Shipment Reference</span>
            <span class="detail-value">{{ $shipmentRef }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Amount (USD)</span>
            <span class="detail-value">${{ $amountUsd }}</span>
        </div>
        @if($amountGhs)
        <div class="detail-row">
            <span class="detail-label">Amount (GHS)</span>
            <span class="detail-value">GH₵{{ $amountGhs }}</span>
        </div>
        @endif
        <div class="detail-row">
            <span class="detail-label">Payment Method</span>
            <span class="detail-value">{{ strtoupper($method) }}</span>
        </div>
        @if($reference)
        <div class="detail-row">
            <span class="detail-label">Reference</span>
            <span class="detail-value">{{ $reference }}</span>
        </div>
        @endif
        <div class="detail-row">
            <span class="detail-label">Date</span>
            <span class="detail-value">{{ $paidOn }}</span>
        </div>
    </div>

    <div class="cta-row">
        <a href="{{ $invoiceUrl }}" class="btn btn-invoice">📄 Download Invoice</a>
        <a href="{{ $receiptUrl }}" class="btn btn-receipt">🧾 View Receipt</a>
    </div>

    <p style="font-size:13px; color:#666; text-align:center;">
        Keep this email for your records. The links above are always accessible.
    </p>

    <div class="footer">
        <p><strong>Thank you for your business!</strong></p>
        <div class="contact-info">
            <p><strong>Ghana Office:</strong> Adako Jachie, Ejisu - Kumasi</p>
            <p>+233 509725073 / +233 50 9725081</p>
            <p style="margin-top:10px;"><strong>USA Office:</strong> Westfield, Indiana</p>
            <p>+1 (773) 970-0129 / +1 (574) 440-7460</p>
        </div>
        <p style="margin-top:20px; color:#999;">This is an automated message. Please do not reply directly to this email.</p>
    </div>
</body>
</html>
