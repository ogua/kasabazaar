<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Shipment Request</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; border-bottom: 3px solid #dc2626; padding-bottom: 20px; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: bold; color: #333; }
        .company-name .rose { color: #dc2626; }
        .alert-banner { background: #fef2f2; border: 2px solid #dc2626; border-radius: 8px; padding: 16px 20px; margin: 20px 0; text-align: center; }
        .alert-title { font-size: 20px; font-weight: bold; color: #dc2626; margin: 0 0 4px; }
        .alert-sub { font-size: 13px; color: #666; margin: 0; }
        .details-box { background: #f3f4f6; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: bold; color: #666; }
        .detail-value { color: #333; }
        .items-box { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin: 12px 0; }
        .items-title { font-size: 13px; font-weight: bold; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .item-row { font-size: 13px; padding: 4px 0; color: #333; }
        .btn { display: inline-block; background: #dc2626; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 10px 0; }
        .footer { text-align: center; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name"><span class="rose">ROSE</span> DOOR TO DOOR SHIPPING AND DELIVERY CO</div>
    </div>

    <div class="alert-banner">
        <p class="alert-title">New Shipment Request</p>
        <p class="alert-sub">A client has submitted a new shipment request requiring your review.</p>
    </div>

    <div class="details-box">
        <div class="detail-row">
            <span class="detail-label">Client Name:</span>
            <span class="detail-value">{{ $client?->name ?? 'N/A' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Phone:</span>
            <span class="detail-value">{{ $client?->phone ?? 'N/A' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Pickup Location:</span>
            <span class="detail-value">{{ $request->pickup_location }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Preferred Pickup:</span>
            <span class="detail-value">{{ $request->preferred_pickup_at?->format('j M Y, g:i A') }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Receivers:</span>
            <span class="detail-value">{{ count($request->receivers) }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Submitted At:</span>
            <span class="detail-value">{{ $request->created_at->format('j M Y, g:i A') }}</span>
        </div>
        @if($request->notes)
        <div class="detail-row">
            <span class="detail-label">Notes:</span>
            <span class="detail-value">{{ $request->notes }}</span>
        </div>
        @endif
    </div>

    @foreach($request->receivers as $i => $receiver)
    <div class="items-box">
        <div class="items-title">Receiver {{ $i + 1 }}: {{ $receiver['name'] }} ({{ $receiver['phone'] ?? '' }})</div>
        @foreach($receiver['items'] ?? [] as $item)
        <div class="item-row">• {{ $item['description'] }} &times; {{ $item['quantity'] }}
            @if(!empty($item['estimated_value'])) — USD {{ number_format($item['estimated_value'], 2) }} @endif
        </div>
        @endforeach
    </div>
    @endforeach

    <div style="text-align: center; margin: 24px 0;">
        <a href="{{ config('app.url') }}/admin" class="btn">Review in Admin Panel</a>
    </div>

    <div class="footer">
        <p>This is an automated notification from the Kasabazaar platform.</p>
    </div>
</body>
</html>
