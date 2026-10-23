@php
    use App\Enums\ShipmentPaymentAttemptStatus;

    $paid = $attempt->status === ShipmentPaymentAttemptStatus::Success;
    $feePercent = rtrim(rtrim(number_format((float) $attempt->fee_percent, 2), '0'), '.');
    $statusColours = match ($attempt->status) {
        ShipmentPaymentAttemptStatus::Success => ['#dcfce7', '#166534', '#16a34a'],
        ShipmentPaymentAttemptStatus::Pending => ['#fef3c7', '#92400e', '#f59e0b'],
        default => ['#fee2e2', '#991b1b', '#dc2626'],
    };
    $method = $attempt->channel ? ucwords(str_replace('_', ' ', $attempt->channel)) : 'Paystack';
    $date = $attempt->paid_at ?? $attempt->created_at;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Payment Receipt - {{ $attempt->reference }}</title>
    <style>
        @include('pdf.partials.invoice-styles')

        .status-pill {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: {{ $statusColours[0] }};
            color: {{ $statusColours[1] }};
            border: 1px solid {{ $statusColours[2] }};
        }

        .status-note {
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 11px;
            margin-bottom: 15px;
            background: {{ $statusColours[0] }};
            color: {{ $statusColours[1] }};
            border: 1px solid {{ $statusColours[2] }};
        }

        .payment-section .info-row {
            font-size: 11px;
        }

        .receipt-actions {
            max-width: 900px;
            margin: 0 auto;
            padding: 16px 20px 0;
            text-align: right;
        }

        .receipt-actions a,
        .receipt-actions button {
            display: inline-block;
            margin: 0 0 8px 8px;
            padding: 9px 18px;
            border-radius: 6px;
            border: 1px solid #003151;
            background: #fff;
            color: #003151;
            font: bold 12px Arial, sans-serif;
            text-decoration: none;
            cursor: pointer;
        }

        .receipt-actions .primary {
            background: #A0043C;
            border-color: #A0043C;
            color: #fff;
        }

        @unless ($forPdf ?? false)
        @media screen {
            body {
                background: #f3f4f6;
            }

            .container {
                max-width: 900px;
                margin: 16px auto 32px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 4px 24px rgba(0, 0, 0, .08);
            }
        }

        @media screen and (max-width: 640px) {
            .container {
                margin: 8px;
                padding: 14px;
            }

            .header-left,
            .header-right,
            .client-info-left,
            .client-info-right,
            .totals-left,
            .totals-right {
                display: block;
                width: 100%;
                text-align: left;
                padding-right: 0;
            }

            .header-right {
                padding: 0 0 12px;
            }

            .client-info-right {
                margin-top: 15px;
            }

            .totals-left {
                margin-bottom: 15px;
            }

            .totals-table {
                width: 100%;
            }

            .receipt-actions {
                text-align: left;
            }

            .receipt-actions a,
            .receipt-actions button {
                margin: 0 8px 8px 0;
            }
        }

        @endunless

        @media print {
            .receipt-actions {
                display: none;
            }

            body {
                background: #fff;
            }

            .container {
                box-shadow: none;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    @unless ($forPdf ?? false)
        <div class="receipt-actions">
            <button type="button" class="primary" onclick="window.print()">Print receipt</button>
            <a href="{{ route('tracking-payment.pdf', $attempt->reference) }}">Download PDF</a>
            @if (! $paid || $balance > 0)
                <a href="{{ route('tracking-payment.create', $shipment->tracking_number) }}">{{ $paid ? 'Pay remaining balance' : 'Try again' }}</a>
            @endif
            <a href="{{ route('our-tracking', ['ref' => $shipment->tracking_number]) }}">Track shipment</a>
        </div>
    @endunless

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if (file_exists(public_path('images/kasarose-logo.png')))
                    {{-- The PDF embeds the logo: fetching it over HTTP from the server that is rendering
                         the PDF can stall (a single-worker server waits on itself). --}}
                    <img src="{{ ($forPdf ?? false) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/kasarose-logo.png'))) : URL::to('images/kasarose-logo.png') }}" alt="KASAROSE LOGISTICS" class="logo"
                        style="max-width: 200px;position: relative; top: -30px;">
                @endif
            </div>
            <div class="header-right">
                <div class="invoice-title">Payment Receipt</div>
                <div class="invoice-details">
                    <p><strong>Receipt No:</strong> {{ $attempt->reference }}</p>
                    <p><strong>Invoice No:</strong> {{ $shipment->shipping_reference ?: '—' }}</p>
                    <p><strong>Date:</strong> {{ $date->format('j M Y, g:i A') }}</p>
                </div>
                <span class="status-pill">{{ $paid ? 'Paid' : $attempt->status->getLabel() }}</span>
            </div>
        </div>

        <!-- Payer & Shipment Info -->
        <div class="section">
            <div class="client-info">
                <div class="client-info-left">
                    <div class="section-title">Received From</div>
                    <div class="info-row">
                        <span class="info-label">Sender:</span>
                        <span class="info-value">{{ $shipment->client?->name ?: '—' }}</span>
                    </div>
                    @if ($attempt->payer_phone)
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span class="info-value">{{ $maskedPhone }}</span>
                        </div>
                    @endif
                    @if ($attempt->payer_email)
                        <div class="info-row">
                            <span class="info-label">Receipt sent to:</span>
                            <span class="info-value">{{ $attempt->payer_email }}</span>
                        </div>
                    @endif
                </div>
                <div class="client-info-right">
                    <div class="section-title">Shipment Details</div>
                    <div class="info-row">
                        <span class="info-label">Tracking No:</span>
                        <span class="info-value">{{ $shipment->tracking_number }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">From:</span>
                        <span class="info-value">{{ $shipment->origin_branch_id }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">To:</span>
                        <span class="info-value">{{ $shipment->destination_branch_id }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">{{ $shipment->status?->getLabel() }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if (! $paid)
            <div class="status-note">
                @if ($attempt->status === ShipmentPaymentAttemptStatus::Pending)
                    We haven&rsquo;t had confirmation from Paystack yet. If you completed the payment it usually
                    shows within a minute &mdash; reload this page. If you left the checkout before paying, nothing was charged.
                @else
                    This payment did not go through{{ $attempt->gateway_response ? ' ('.$attempt->gateway_response.')' : '' }}.
                    If money left your account, call us with the receipt number above and we&rsquo;ll sort it out.
                @endif
            </div>
        @endif

        <!-- Payment lines -->
        <div class="section">
            <div class="section-title">Payment Details</div>
            <table class="receiver-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Description</th>
                        <th style="width: 120px;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Credited to shipment {{ $shipment->shipping_reference ?: $shipment->tracking_number }}</td>
                        <td class="text-right">${{ number_format((float) $attempt->amount_usd, 2) }}</td>
                    </tr>
                    @if ((float) $attempt->fee_usd > 0)
                        <tr>
                            <td>2</td>
                            <td>Transaction charge ({{ $feePercent }}%) &mdash; card / mobile money processing fee</td>
                            <td class="text-right">${{ number_format((float) $attempt->fee_usd, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Tracking Number -->
        @if ($shipment->publicTrackingUrl())
            <a href="{{ $shipment->publicTrackingUrl() }}" target="_blank" class="track-box">
                <div class="track-label">Click to <span class="track-word">TRACK</span> Your Shipment</div>
                <div class="track-number">{{ $shipment->tracking_number ?: $shipment->shipping_reference }}</div>
            </a>
        @endif

        <!-- Payment info & totals -->
        <div class="totals-section">
            <div class="totals-left">
                <div class="payment-section">
                    <div class="payment-title" style="text-align: center;font-weight: bold;font-size: 16px;color: #003151;">
                        Payment Information
                    </div>
                    <div class="info-row"><span class="info-label">Method:</span> <span class="info-value">{{ $method }} (Paystack)</span></div>
                    <div class="info-row"><span class="info-label">Receipt No:</span> <span class="info-value">{{ $attempt->reference }}</span></div>
                    @if ($attempt->currency !== 'USD')
                        <div class="info-row">
                            <span class="info-label">Charged in cedis:</span>
                            <span class="info-value">{{ $attempt->currency }} {{ number_format($attempt->amount_subunit / 100, 2) }}
                                @if ($attempt->exchange_rate)(at $1 = {{ $attempt->currency }} {{ number_format((float) $attempt->exchange_rate, 2) }})@endif</span>
                        </div>
                    @endif
                    @if ($attempt->paid_at)
                        <div class="info-row"><span class="info-label">Paid on:</span> <span class="info-value">{{ $attempt->paid_at->format('j M Y, g:i A') }}</span></div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Verify:</span>
                        <span class="info-value">{{ route('tracking-payment.verify') }}</span>
                    </div>
                </div>
            </div>
            <div class="totals-right">
                <table class="totals-table" style="margin-left: auto;">
                    <tr>
                        <td class="total-label">Invoice Total:</td>
                        <td class="total-value">${{ number_format((float) $shipment->total, 2) }}</td>
                    </tr>
                    @if ($paid)
                        <tr>
                            <td class="total-label">Paid Before:</td>
                            <td class="total-value">${{ number_format($paidBefore, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="total-label">Credited to shipment:</td>
                        <td class="total-value">${{ number_format((float) $attempt->amount_usd, 2) }}</td>
                    </tr>
                    @if ((float) $attempt->fee_usd > 0)
                        <tr>
                            <td class="total-label">Transaction charge ({{ $feePercent }}%):</td>
                            <td class="total-value">${{ number_format((float) $attempt->fee_usd, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td class="total-label" style="color: white;">Total Charged:</td>
                        <td class="total-value" style="color: white;">${{ number_format($attempt->totalUsd(), 2) }}</td>
                    </tr>
                    @if ($paid)
                        <tr>
                            <td class="total-label">Balance Remaining:</td>
                            <td class="total-value" style="color: {{ $balance > 0 ? '#dc2626' : '#16a34a' }};">
                                {{ $balance > 0 ? '$'.number_format($balance, 2) : 'Fully paid' }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            @if ($paid)
                <p style="font-size: 11px;">Verified. This payment was confirmed by Paystack and recorded against the shipment above.</p>
            @endif
            <p style="font-size: 11px;">Check this receipt any time at {{ route('tracking-payment.verify') }} with receipt number {{ $attempt->reference }}.</p>
            <p style="font-weight: bold">Thank you for choosing KASAROSE LOGISTICS!</p>
        </div>
    </div>
</body>

</html>
