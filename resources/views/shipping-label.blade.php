<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Label - {{ $shipping->shipping_reference }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: 4in 6in;
            margin: 0;
        }

        @media print {
            html, body {
                width: 4in;
                height: 6in;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .label-container {
                page-break-after: always;
                box-shadow: none;
                border: none;
            }
            .label-container:last-child {
                page-break-after: avoid;
            }
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f0f0f0;
            font-size: 9px;
        }

        .print-controls {
            background: #333;
            color: white;
            padding: 10px;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .print-controls button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 24px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 4px;
            margin: 0 5px;
        }

        .print-controls button:hover {
            background: #45a049;
        }

        .print-controls .back-btn {
            background: #666;
        }

        .labels-wrapper {
            padding-top: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            padding-bottom: 15px;
        }

        .label-container {
            width: 4in;
            height: 6in;
            background: white;
            border: 1px solid #000;
            padding: 6px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            overflow: hidden;
        }

        /* Header */
        .label-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 4px;
            margin-bottom: 3px;
        }

        .company-info {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .company-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        .company-name {
            font-size: 9px;
            font-weight: bold;
            color: #000;
            max-width: 180px;
            line-height: 1.1;
        }

        .reference-box {
            text-align: right;
        }

        .reference-label {
            font-size: 6px;
            color: #666;
            text-transform: uppercase;
        }

        .reference-number {
            font-size: 9px;
            font-weight: bold;
            color: #000;
            background: #eee;
            padding: 2px 4px;
            border: 1px solid #000;
        }

        /* Tracking */
        .tracking-section {
            text-align: center;
            padding: 3px 0;
            border-bottom: 1px dashed #666;
            margin-bottom: 3px;
        }

        .tracking-label {
            font-size: 6px;
            color: #666;
            text-transform: uppercase;
        }

        .tracking-number {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .barcode-placeholder {
            font-family: 'Libre Barcode 39', monospace;
            font-size: 24px;
            line-height: 1;
        }

        /* Addresses */
        .address-section {
            display: flex;
            flex-direction: column;
            gap: 3px;
            margin-bottom: 3px;
        }

        .address-block {
            padding: 4px;
            border: 1px solid #ccc;
            border-radius: 2px;
        }

        .address-block.from {
            background: #f5f5f5;
        }

        .address-block.to {
            background: #fffde7;
            border: 1px solid #000;
        }

        .address-label {
            font-size: 7px;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            margin-bottom: 1px;
        }

        .address-name {
            font-size: 11px;
            font-weight: bold;
            color: #000;
        }

        .address-details {
            font-size: 8px;
            color: #333;
            line-height: 1.2;
        }

        .address-phone {
            font-size: 9px;
            font-weight: bold;
            margin-top: 1px;
        }

        /* Items */
        .items-section {
            flex: 1;
            border: 1px solid #000;
            border-radius: 2px;
            padding: 3px;
            margin-bottom: 3px;
            overflow: hidden;
            min-height: 0;
        }

        .items-header {
            font-size: 7px;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
            margin-bottom: 2px;
        }

        .items-list {
            font-size: 7px;
            line-height: 1.3;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
        }

        .item-name {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-right: 4px;
        }

        .item-qty {
            font-weight: bold;
            white-space: nowrap;
        }

        .items-total {
            font-size: 8px;
            font-weight: bold;
            text-align: right;
            padding-top: 2px;
            margin-top: 2px;
            border-top: 1px solid #000;
        }

        /* Info Bar */
        .shipment-info {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            border-top: 2px solid #000;
        }

        .info-item {
            text-align: center;
            flex: 1;
        }

        .info-label {
            font-size: 6px;
            color: #666;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 8px;
            font-weight: bold;
        }

        /* Footer */
        .label-footer {
            text-align: center;
            font-size: 6px;
            color: #888;
            padding-top: 2px;
            border-top: 1px solid #ddd;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap" rel="stylesheet">
</head>
<body>
    <div class="print-controls no-print">
        <button onclick="window.print()">Print Labels</button>
        <button class="back-btn" onclick="window.history.back()">Back</button>
    </div>

    <div class="labels-wrapper">
        @foreach ($shipping->receivers as $index => $receiver)
        <div class="label-container">
            <!-- Header with Logo -->
            <div class="label-header">
                <div class="company-info">
                    <img src="/images/kasabazaar-logo.png" alt="Logo" class="company-logo">
                    <div class="company-name">Rose Door to Door Shipping and Delivery Services</div>
                </div>
                <div class="reference-box">
                    <div class="reference-label">Ref</div>
                    <div class="reference-number">{{ $shipping->shipping_reference }}</div>
                </div>
            </div>

            <!-- Tracking Section -->
            <div class="tracking-section">
                <div class="tracking-label">Tracking Number</div>
                <div class="tracking-number">{{ $shipping->tracking_number }}</div>
                <div class="barcode-placeholder">*{{ $shipping->tracking_number }}*</div>
            </div>

            <!-- Address Section -->
            <div class="address-section">
                <!-- From Address -->
                <div class="address-block from">
                    <div class="address-label">From</div>
                    <div class="address-name">{{ $shipping->client?->name ?? 'N/A' }}</div>
                    <div class="address-details">{{ $shipping->origin_branch_id }}, USA </div>
                </div>

                <!-- To Address -->
                <div class="address-block to">
                    <div class="address-label">To (Receiver #{{ $index + 1 }} of {{ $shipping->receivers->count() }})</div>
                    <div class="address-name">{{ $receiver->receiver_name }}</div>
                    <div class="address-details">
                        @if($receiver->address){{ $receiver->address }}, @endif{{ $receiver->mcity?->name ?? '' }}{{ $receiver->mcity && $receiver->mstate ? ', ' : '' }}{{ $receiver->mstate?->name ?? '' }}, {{ $receiver->mcountry?->name ?? $shipping->destination_branch_id }}
                    </div>
                    {{-- @if($receiver->receiver_phone)
                    <div class="address-phone">Tel: {{ $receiver->receiver_phone }}</div>
                    @endif --}}
                </div>
            </div>

            <!-- Items Section -->
            <div class="items-section">
                <div class="items-header">Package Contents ({{ $receiver->items->count() }} item{{ $receiver->items->count() != 1 ? 's' : '' }})</div>
                <div class="items-list">
                    @foreach($receiver->items->take(6) as $item)
                    <div class="item-row">
                        <span class="item-name">{{ $item->product?->name ?? 'Item' }}</span>
                        <span class="item-qty">x{{ $item->quantity }}</span>
                    </div>
                    @endforeach
                    @if($receiver->items->count() > 6)
                    <div class="item-row">
                        <span class="item-name" style="font-style: italic; color: #666;">+ {{ $receiver->items->count() - 6 }} more item{{ ($receiver->items->count() - 6) != 1 ? 's' : '' }}</span>
                    </div>
                    @endif
                </div>
                <div class="items-total">
                    {{ $receiver->items->sum('quantity') }} units | ${{ number_format($receiver->items->sum('item_cost'), 2) }}
                </div>
            </div>

            <!-- Shipment Info -->
            <div class="shipment-info">
                <div class="info-item">
                    <div class="info-label">Route</div>
                    <div class="info-value">{{ $shipping->origin_branch_id }} &rarr; {{ $shipping->destination_branch_id }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Container</div>
                    <div class="info-value">CON{{ $shipping->container_number }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date</div>
                    <div class="info-value">{{ $shipping->shipped_at ? \Carbon\Carbon::parse($shipping->shipped_at)->format('m/d/y') : now()->format('m/d/y') }}</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="label-footer">
                {{ now()->format('M d, Y H:i') }} | rddshipping.com
            </div>
        </div>
        @endforeach
    </div>

    <script>
        // Auto-print option (uncomment if needed)
        window.onload = function() { window.print(); }
    </script>
</body>
</html>
