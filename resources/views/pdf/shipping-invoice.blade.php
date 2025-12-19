<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Invoice - {{ $shipping->shipping_reference }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 100%;
            padding: 20px;
        }

        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #dc2626;
        }

        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .logo {
            max-width: 180px;
            height: auto;
            margin-top: 5px;
            margin-left: 5px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .company-name .rose {
            color: #dc2626;
        }

        .company-info {
            font-size: 10px;
            color: #666;
            line-height: 1.6;
        }

        .company-info .location-label {
            color: #4169E1;
            font-weight: bold;
        }

        .amount-box {
            background: #dc2626;
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 10px;
        }

        .amount-label {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .amount-value {
            font-size: 24px;
            font-weight: bold;
            margin: 5px 0;
        }

        .balance {
            font-size: 11px;
            text-align: right;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #4169E1;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .invoice-details {
            font-size: 11px;
            color: #666;
        }

        .invoice-details strong {
            color: #333;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #4169E1;
            border-bottom: 2px solid #dc2626;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .client-info {
            display: table;
            width: 100%;
        }

        .client-info-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .client-info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .info-row {
            margin-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
            color: #666;
        }

        .info-value {
            color: #4169E1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background: transparent;
            color: #333;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            border-bottom: 2px solid #dc2626;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }



        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .receiver-box {
            background: #1e40af;
            color: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .receiver-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .receiver-details {
            font-size: 10px;
            opacity: 0.9;
        }

        .receiver-table th {
            background: #4169E1;
        }

        .totals-section {
            margin-top: 20px;
            border-top: 2px solid #e5e7eb;
            padding-top: 15px;
            display: table;
            width: 100%;
        }

        .totals-left {
            display: table-cell;
            width: 60%;
            vertical-align: bottom;
            padding-right: 20px;
        }

        .totals-right {
            display: table-cell;
            width: 40%;
            vertical-align: bottom;
            text-align: right;
        }

        .payment-section {
            padding: 15px;
            background: #f3f4f6;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .balance-inline {
            padding: 8px 15px;
            background: #fee2e2;
            border-radius: 6px;
            border: 1px solid #dc2626;
            text-align: center;
        }

        .totals-table {
            width: 300px;
            margin-left: auto;
        }

        .totals-table td {
            padding: 6px 10px;
            border: none;
        }

        .totals-table .total-label {
            font-weight: bold;
            color: #666;
        }

        .totals-table .total-value {
            text-align: right;
            font-weight: bold;
        }

        .grand-total {
            background: #dc2626;
            color: white;
        }

        .grand-total td {
            font-size: 14px;
            padding: 10px;
        }



        .payment-title {
            font-size: 12px;
            font-weight: bold;
            color: #666;
            margin-bottom: 10px;
        }

        .payment-methods {
            display: table;
            width: 100%;
        }

        .payment-method {
            display: table-cell;
            width: 50%;
            padding: 10px;
        }

        .payment-method h4 {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .payment-method p {
            font-size: 11px;
            color: #666;
            margin-bottom: 3px;
        }

        .footer {
            margin-top: 30px;
            padding: 10px;
            background: #dc2626;
            color: white;
            text-align: center;
            border-radius: 6px;
        }

        .footer p {
            font-size: 12px;
            font-style: italic;
        }

        .tracking-info {
            background: #f0f9ff;
            border: 1px solid #4169E1;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 15px;
        }

        .tracking-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }

        .tracking-number {
            font-size: 16px;
            font-weight: bold;
            color: #4169E1;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if (file_exists(public_path('images/kasabazaar-logo.png')))
                    <img src="{{ URL::to('images/kasabazaar-logo.png') }}" alt="Logo" class="logo"
                        style="max-width: 300px;position: relative; top: -30px;">
                @endif

                {{-- <div class="company-name">
                    <span class="rose">ROSE</span> DOOR TO DOOR SHIPPING AND DELIVERY CO
                </div> --}}

                {{-- <div class="company-info">
                    <p><span class="location-label">GH</span> - Adako Jachie, Ejisu - Kumasi, Ghana</p>
                    <p>+233 509725073 / +233 50 9725081</p>
                    <p style="margin-top: 5px;"><span class="location-label">USA</span> - Westfield, Indiana - USA</p>
                    <p>+1 (773) 970-0129 / +1 (574) 440-7460</p>
                </div> --}}
            </div>
            <div class="header-right">
                @php
                    $balance = $shipping->total - $shipping->payments->sum('amount');
                    if ($balance < 0) {
                        $balance = 0;
                    }
                @endphp
                {{-- <div class="amount-box">
                    <div class="amount-label">Amount Paid</div>
                    <div class="amount-value">${{ number_format($shipping->payments->sum('amount'), 2) }}</div>
                   
                    <div class="balance">Balance: ${{ number_format($balance, 2) }}</div>
                </div> --}}
                <div class="invoice-title">SHIPPING INVOICE</div>
                <div class="invoice-details">
                    <p><strong>Invoice No:</strong> {{ $shipping->shipping_reference }}</p>
                    <p><strong>Date:</strong> {{ $shipping->created_at->format('j M Y') }}</p>
                    <p><strong>Tracking:</strong> {{ $shipping->tracking_number }}</p>
                </div>
            </div>
        </div>

        <!-- Client & Shipping Info -->
        <div class="section">
            <div class="client-info">
                <div class="client-info-left">
                    <div class="section-title">Sender</div>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value">{{ $shipping->client?->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value">{{ $shipping->client?->phone }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">{{ $shipping->client?->email }}</span>
                    </div>
                    @if ($shipping->client?->address)
                        <div class="info-row">
                            <span class="info-label">Address:</span>
                            <span class="info-value">{{ $shipping->client?->address }}</span>
                        </div>
                    @endif
                </div>
                <div class="client-info-right">
                    <div class="section-title">Shipping Details</div>
                    <div class="info-row">
                        <span class="info-label">From:</span>
                        <span class="info-value">{{ $shipping->origin_branch_id }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">To:</span>
                        <span class="info-value">{{ $shipping->destination_branch_id }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">{{ ucfirst($shipping->status->value) }}</span>
                    </div>
                    @if ($shipping->estimated_delivery_date)
                        <div class="info-row">
                            <span class="info-label">Est. Delivery:</span>
                            <span
                                class="info-value">{{ \Carbon\Carbon::parse($shipping->estimated_delivery_date)->format('j M Y') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tracking Info -->
        {{-- <div class="tracking-info">
            <span class="tracking-label">Tracking Number</span>
            <div class="tracking-number">{{ $shipping->tracking_number }}</div>
        </div> --}}

        <!-- Pickup Items (if any) -->
        @if ($shipping->pickupitems && count($shipping->receivers) > 1)
            <div class="section">
                <div class="section-title">Pickup Items</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Item Description</th>
                            <th style="width: 80px;" class="text-center">Qty</th>
                            <th style="width: 100px;" class="text-right">Unit Cost</th>
                            <th style="width: 100px;" class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($shipping->pickupitems as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->product?->name ?? 'N/A' }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-right">${{ number_format($item->item_cost, 2) }}</td>
                                <td class="text-right">${{ number_format($item->quantity * $item->item_cost, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr style="background: #f3f4f6; font-weight: bold;">
                            <td colspan="2">Pickup Items Subtotal</td>
                            <td class="text-center">{{ $shipping->pickupitems->sum('quantity') }}</td>
                            <td></td>
                            <td class="text-right">
                                ${{ number_format($shipping->pickupitems->sum(fn($i) => $i->quantity * $i->item_cost), 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Receivers and Their Items -->
        @if ($shipping->receivers && $shipping->receivers->count() > 0)
            <div class="section">
                <div class="section-title">Receivers & Shipping Items</div>

                @foreach ($shipping->receivers as $receiverIndex => $receiver)
                    <div class="receiver-box">
                        <div class="receiver-name">
                            Receiver {{ $receiverIndex + 1 }}: {{ $receiver->receiver_name }}
                        </div>
                        <div class="receiver-details">
                            @if ($receiver->receiver_phone)
                                Phone: {{ $receiver->receiver_phone }} |
                            @endif
                            @if ($receiver->receiver_email)
                                Email: {{ $receiver->receiver_email }}
                            @endif
                            @if ($receiver->mcountry || $receiver->mstate || $receiver->mcity)
                                <br>Location:
                                {{ $receiver->mcountry?->name }}{{ $receiver->mstate ? ', ' . $receiver->mstate->name : '' }}{{ $receiver->mcity ? ', ' . $receiver->mcity->name : '' }}
                            @endif
                            @if ($receiver->address)
                                <br>Address: {{ $receiver->address }}
                            @endif
                            @if ($receiver->receiver_id_type)
                                <br>ID: {{ $receiver->receiver_id_type }} - {{ $receiver->receiver_id_number }}
                            @endif
                        </div>
                    </div>

                    @if ($receiver->items && $receiver->items->count() > 0)
                        <table class="receiver-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Item Description</th>
                                    <th style="width: 80px;" class="text-center">Qty</th>
                                    <th style="width: 100px;" class="text-right">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($receiver->items as $itemIndex => $item)
                                    <tr>
                                        <td>{{ $itemIndex + 1 }}</td>
                                        <td>{{ $item->product?->name ?? 'N/A' }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-right">${{ number_format($item->item_cost, 2) }}</td>
                                    </tr>
                                @endforeach
                                {{-- <tr style="background: #e0e7ff; font-weight: bold;">
                                    <td colspan="2">Receiver Subtotal</td>
                                    <td class="text-center">{{ $receiver->items->sum('quantity') }}</td>
                                    <td class="text-right">${{ number_format($receiver->items->sum('item_cost'), 2) }}
                                    </td>
                                </tr> --}}
                            </tbody>
                        </table>
                    @endif
                @endforeach
            </div>
        @endif

        <!-- Totals & Payment Methods -->
        <div class="totals-section">
            <div class="totals-left" style="margin-right: 20px;">
                <div class="payment-section">
                    <div class="payment-title"
                        style="text-align: center;font-weight: bold;font-size: 16px;color: #4169E1;">Payment Methods:
                    </div>
                    <div class="payment-methods">
                        <div class="payment-method">
                            <img src="{{ URL::to('images/cash-app.png') }}" alt="Logo" class="logo"
                                style="max-width: 120px; margin-bottom: 5px;">
                            <p style="font-size: 11px;font-weight: bold; margin-bottom: 2px;">Phone #: (317) 774-6913
                            </p>
                            <p style="font-size: 11px;font-weight: bold; margin-bottom: 2px;">Name: Rose Adel</p>
                            <p style="font-size: 11px;font-weight: bold;">$KasaBazaar1</p>
                        </div>
                        <div class="payment-method" style="text-align: right;">
                            <img src="{{ URL::to('images/zelle.png') }}" alt="Logo" class="logo"
                                style="max-width: 120px; margin-bottom: 5px;">
                            <p style="font-size: 11px;font-weight: bold; margin-bottom: 2px;">Phone #: (317) 774-6913
                            </p>
                            <p style="font-size: 11px;font-weight: bold;">Name: Kasabazaar</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="totals-right">
                <table class="totals-table" style="margin-left: auto;">
                    {{-- @if ($shipping->pickupitems && $shipping->pickupitems->count() > 0)
                        <tr>
                            <td class="total-label">Pickup Items Total:</td>
                            <td class="total-value">
                                ${{ number_format($shipping->pickupitems->sum(fn($i) => $i->quantity * $i->item_cost), 2) }}
                            </td>
                        </tr>
                    @endif --}}
                    <tr>
                        <td class="total-label">Shipping Cost:</td>
                        <td class="total-value">${{ number_format($shipping->shipping_cost, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">Subtotal:</td>
                        <td class="total-value">${{ number_format($shipping->total, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">Tax (0%):</td>
                        <td class="total-value">$0.00</td>
                    </tr>
                    <tr>
                        <td class="total-label">Discount:</td>
                        <td class="total-value">${{ number_format($shipping->discount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">Insurance:</td>
                        <td class="total-value">${{ number_format($shipping->insurance, 2) }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td class="total-label" style="color: white;">Grand Total:</td>
                        <td class="total-value" style="color: white;">${{ number_format($shipping->total, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">Amount Paid:</td>
                        <td class="total-value" style="color: #16a34a;">
                            ${{ number_format($shipping->payments->sum('amount'), 2) }}</td>
                    </tr>
                    @if ($balance > 0)
                        <tr>
                            <td class="total-label">Balance Due:</td>
                            <td class="total-value" style="color: #dc2626;">${{ number_format($balance, 2) }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="font-size: 11px;">Goods shipped without insurance cover does not guarantee full compensation in case of damage or loss.</p>
            <p style="font-size: 11px;">All used and Non-Manufactured packed items are shipped without warranty.</p>
            <p style="font-weight: bold">Thank you for choosing Rose Door To Door Shipping and Delivery Co!</p>
        </div>
    </div>
</body>

</html>
