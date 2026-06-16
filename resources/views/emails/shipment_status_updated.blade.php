<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Status Update</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; border-bottom: 3px solid #A0043C; padding-bottom: 20px; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: bold; color: #333; }
        .company-name .rose { color: #A0043C; }
        .status-banner { border-radius: 10px; padding: 18px; text-align: center; margin: 20px 0; }
        .status-banner.cleared   { background: #16a34a; color: white; }
        .status-banner.shipped   { background: #0369a1; color: white; }
        .status-banner.delivered { background: #15803d; color: white; }
        .status-banner.pickup    { background: #d97706; color: white; }
        .status-banner.pending   { background: #dc2626; color: white; }
        .status-banner.cancelled { background: #6b7280; color: white; }
        .status-banner.default   { background: #003151; color: white; }
        .banner-icon  { font-size: 36px; }
        .banner-title { font-size: 20px; font-weight: bold; margin-top: 8px; }
        .banner-sub   { font-size: 14px; margin-top: 4px; opacity: 0.9; }
        .ref-box { background: #f0f9ff; border: 2px solid #003151; border-radius: 8px; padding: 15px; text-align: center; margin: 20px 0; }
        .ref-label  { font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 1px; }
        .ref-number { font-size: 22px; font-weight: bold; color: #003151; }
        .details-box { background: #f3f4f6; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: bold; color: #666; }
        .detail-value { color: #003151; font-weight: 500; }
        .note-box { background: #fffbeb; border-left: 4px solid #d97706; border-radius: 4px; padding: 14px 18px; margin: 20px 0; font-size: 14px; color: #555; }
        .footer { text-align: center; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #666; font-size: 12px; }
        .contact-info p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">
            <span class="rose">ROSE</span> DOOR TO DOOR SHIPPING AND DELIVERY CO
        </div>
    </div>

    @php
        $bannerClass = match($status) {
            'cleared'   => 'cleared',
            'shipped'   => 'shipped',
            'delivered' => 'delivered',
            'pickup'    => 'pickup',
            'pending'   => 'pending',
            'cancelled' => 'cancelled',
            default     => 'default',
        };
        $icon = match($status) {
            'cleared'   => '✅',
            'shipped'   => '🚢',
            'delivered' => '📦',
            'pickup'    => '🏪',
            'pending'   => '⏳',
            'cancelled' => '❌',
            default     => '🔔',
        };
        $statusLabel = ucfirst($status);
    @endphp

    <div class="status-banner {{ $bannerClass }}">
        <div class="banner-icon">{{ $icon }}</div>
        <div class="banner-title">Shipment Status: {{ $statusLabel }}</div>
        <div class="banner-sub">Your shipment has been updated</div>
    </div>

    <p>Dear {{ $clientName }},</p>
    <p>We would like to inform you that the status of your shipment has been updated. Please see the details below.</p>

    <div class="ref-box">
        <div class="ref-label">Shipment Reference</div>
        <div class="ref-number">{{ $shipmentRef }}</div>
        @if(!empty($containerRef))
        <div style="font-size: 13px; color: #666; margin-top: 6px;">Container: {{ $containerRef }}</div>
        @endif
    </div>

    <div class="details-box">
        <div class="detail-row">
            <span class="detail-label">New Status</span>
            <span class="detail-value">{{ $statusLabel }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Updated On</span>
            <span class="detail-value">{{ $updatedAt }}</span>
        </div>
        @if(!empty($receiverName))
        <div class="detail-row">
            <span class="detail-label">Receiver</span>
            <span class="detail-value">{{ $receiverName }}</span>
        </div>
        @endif
    </div>

    @if(!empty($note))
    <div class="note-box">
        <strong>Note from our team:</strong><br>
        {{ $note }}
    </div>
    @endif

    <p style="font-size: 13px; color: #666;">
        If you have any questions about your shipment, please contact us using the information below.
    </p>

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
