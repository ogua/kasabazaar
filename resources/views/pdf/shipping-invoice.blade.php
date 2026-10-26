<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Invoice - {{ $shipping->shipping_reference }}</title>
    <style>
        @include('pdf.partials.invoice-styles')
    </style>
</head>

<body>
    @if (file_exists(public_path('images/kasarose-logo.png')))
        <div class="watermark">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/kasarose-logo.png'))) }}"
                alt="">
        </div>
    @endif

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if (file_exists(public_path('images/kasarose-logo.png')))
                    <img src="{{ URL::to('images/kasarose-logo.png') }}" alt="Logo" class="logo"
                        style="max-width: 160px;position: relative; top: -30px;">
                @endif

                {{-- <div class="company-name">
                    <span class="rose">KASAROSE</span> LOGISTICS
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
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-details">
                    <p><strong>Invoice No:</strong> {{ $shipping->shipping_reference }}</p>
                    <p><strong>Date:</strong> {{ $shipping->created_at->format('j M Y') }}</p>
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

        <!-- Client Note -->
        @if ($shipping->client_note)
            <div class="client-note-box">
                <div class="client-note-title">Special Note from Client:</div>
                <div class="client-note-content">{{ $shipping->client_note }}</div>
            </div>
        @endif

        <!-- Shipment Description -->
        @if ($shipping->description)
            <div class="client-note-box">
                <div class="client-note-title">Shipment Description:</div>
                <div class="client-note-content">{{ $shipping->description }}</div>
            </div>
        @endif

        <!-- Totals & Payment Methods -->
        <div class="totals-section">
            <div class="totals-left" style="width: 45%; vertical-align: top; text-align: left;">
                <table class="totals-table" style="width: 100%; margin-left: 0;">
                    <tr>
                        <td class="total-label">Shipping Cost:</td>
                        <td class="total-value">${{ number_format($shipping->shipping_cost, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">Subtotal:</td>
                        <td class="total-value">${{ number_format($shipping->total, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">VAT ({{ number_format($shipping->vat_percentage ?? 0, 1) }}%):</td>
                        <td class="total-value">${{ number_format($shipping->vat ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">Discount:</td>
                        <td class="total-value">${{ number_format($shipping->discount ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">Insurance:</td>
                        <td class="total-value">${{ number_format($shipping->insurance ?? 0, 2) }}</td>
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
            <div class="totals-right" style="width: 55%; vertical-align: top; text-align: left;">
                <div class="payment-section" style="margin-bottom: 0;">
                    <div class="payment-title" style="text-align: center;font-weight: bold;font-size: 16px;color: #003151;">
                        Payment Methods:
                    </div>
                    <div class="payment-methods">
                        <div class="payment-method">
                            <img src="{{ URL::to('images/cash-app.png') }}" alt="Logo" class="logo"
                                style="max-width: 120px; margin-bottom: 5px;">
                            <p style="font-size: 11px;font-weight: bold; margin-bottom: 2px;">Phone #: (317) 774-6913</p>
                            <p style="font-size: 11px;font-weight: bold; margin-bottom: 2px;">Name: Rose Adel</p>
                            <p style="font-size: 11px;font-weight: bold;">$KasaBazaar1</p>
                        </div>
                        <div class="payment-method" style="text-align: right;">
                            <img src="{{ URL::to('images/zelle.png') }}" alt="Logo" class="logo"
                                style="max-width: 120px; margin-bottom: 5px;">
                            <p style="font-size: 11px;font-weight: bold; margin-bottom: 2px;">Phone #: (317) 774-6913</p>
                            <p style="font-size: 11px;font-weight: bold;">Name: Kasabazaar</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tracking Number -->
        @if ($shipping->publicTrackingUrl())
            <a href="{{ $shipping->publicTrackingUrl() }}" target="_blank" class="track-box">
                <div class="track-label">Click to <span class="track-word">TRACK</span> Your Shipment</div>
                <div class="track-number">{{ $shipping->tracking_number ?: $shipping->shipping_reference }}</div>
            </a>
        @endif

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box left">
                <div class="signature-title">Shipment Manager Signature</div>
                @if (file_exists(public_path('images/shipping-signature.png')))
                    <img src="{{ URL::to('images/shipping-signature.png') }}" alt="Manager Signature" class="signature-image">
                @else
                    <div class="signature-line"></div>
                @endif
                <div class="signature-label">Authorized Signature</div>
                <div class="signature-label" style="margin-top: 5px;">Date: {{ now()->format('j M Y') }}</div>
            </div>
        </div>

        <!-- Insurance Status -->
        <div style="margin-top: 15px; text-align: center;">
            @if ($shipping->insurance_accepted)
                <span class="insurance-status insurance-accepted">
                    ✓ Insurance Accepted - Coverage: ${{ number_format($shipping->insurance ?? 0, 2) }}
                </span>
            @else
                <span class="insurance-status insurance-declined">
                    ✗ Insurance Declined - No coverage for damage or loss
                </span>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="font-size: 11px;">Goods shipped without insurance cover does not guarantee full compensation in case of damage or loss.</p>
            <p style="font-size: 11px;">All used and Non-Manufactured packed items are shipped without warranty.</p>
            <p style="font-weight: bold">Thank you for choosing KASAROSE LOGISTICS!</p>
        </div>
    </div>
</body>

</html>
