@php
    $client = $receipt->client;
    $logoPath = public_path('images/kasarose-logo.png');
    $companyName = config('group.parent.name', 'KASAROSE LOGISTICS');
    $contact = config('website.contact');
    // Same manager signature as the shipping invoice, embedded so the PDF needs no HTTP fetch.
    $signaturePath = public_path('images/shipping-signature.png');
    $signature = file_exists($signaturePath)
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($signaturePath))
        : null;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Receipt {{ $receipt->receipt_number }}</title>
    <style>
        @include('pdf.partials.invoice-styles')

        .paid-stamp {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #16a34a;
        }

        .amount-words {
            margin: 4px 0 15px;
            padding: 10px 12px;
            border-left: 4px solid #A0043C;
            background: #f9fafb;
            font-size: 12px;
            font-style: italic;
        }

        .company-lines {
            font-size: 10px;
            color: #555;
            line-height: 1.5;
        }

        .mr-signature {
            margin-top: 20px;
            width: 240px;
            page-break-inside: avoid;
        }

        .mr-signature-image {
            max-height: 48px;
            margin-bottom: 2px;
        }

        .mr-signature-sign {
            text-align: center;
            height: 50px;
        }

        .mr-signature-line {
            border-top: 1px solid #333;
            padding-top: 4px;
            font-size: 11px;
            text-align: center;
        }

        .mr-signature-date {
            font-size: 10px;
            color: #555;
            text-align: center;
            margin-top: 2px;
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
            @isset($downloadUrl)
                <a href="{{ $downloadUrl }}">Download PDF</a>
            @endisset
        </div>
    @endunless

    <div class="container">
        <div class="header">
            <div class="header-left">
                @if (file_exists($logoPath))
                    {{-- Embedded in the PDF: fetching it over HTTP from the rendering server can stall. --}}
                    <img src="{{ ($forPdf ?? false) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : URL::to('images/kasarose-logo.png') }}"
                        alt="{{ $companyName }}" class="logo" style="max-width: 200px; position: relative; top: -30px;">
                @endif
                <div class="company-lines">
                    <strong>{{ $companyName }}</strong><br>
                    GH: {{ $contact['address_gh'] ?? '' }} &middot; {{ $contact['phone_gh_1'] ?? '' }}<br>
                    USA: {{ $contact['phone_us_1'] ?? '' }} &middot; {{ $contact['email_support'] ?? '' }}
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">Payment Receipt</div>
                <div class="invoice-details">
                    <p><strong>Receipt No:</strong> {{ $receipt->receipt_number }}</p>
                    <p><strong>Date Paid:</strong> {{ $receipt->paid_on->format('j M Y') }}</p>
                    <p><strong>Date Issued:</strong> {{ $receipt->created_at?->format('j M Y') }}</p>
                </div>
                <span class="paid-stamp">Paid</span>
            </div>
        </div>

        <div class="section">
            <div class="client-info">
                <div class="client-info-left">
                    <div class="section-title">Received From</div>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value">{{ $client?->name ?: '—' }}</span>
                    </div>
                    @if ($client?->phone)
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span class="info-value">{{ $client->phone }}</span>
                        </div>
                    @endif
                    @if ($client?->email)
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value">{{ $client->email }}</span>
                        </div>
                    @endif
                    @if ($client?->address)
                        <div class="info-row">
                            <span class="info-label">Address:</span>
                            <span class="info-value">{{ $client->address }}</span>
                        </div>
                    @endif
                </div>
                <div class="client-info-right">
                    <div class="section-title">Payment Information</div>
                    <div class="info-row">
                        <span class="info-label">Method:</span>
                        <span class="info-value">{{ $receipt->payment_method }}</span>
                    </div>
                    @if ($receipt->transaction_reference)
                        <div class="info-row">
                            <span class="info-label">Transaction Ref:</span>
                            <span class="info-value">{{ $receipt->transaction_reference }}</span>
                        </div>
                    @endif
                    @if ($receipt->related_reference)
                        <div class="info-row">
                            <span class="info-label">Shipment / Invoice:</span>
                            <span class="info-value">{{ $receipt->related_reference }}</span>
                        </div>
                    @endif
                    @if ($receipt->received_by)
                        <div class="info-row">
                            <span class="info-label">Received By:</span>
                            <span class="info-value">{{ $receipt->received_by }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Payment Details</div>
            <table class="receiver-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Description</th>
                        <th style="width: 140px;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>{{ $receipt->description }}</td>
                        <td class="text-right">{{ $receipt->formattedAmount() }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="amount-words">Amount in words: {{ $receipt->amountInWords() }}.</div>
        </div>

        <div class="totals-section">
            <div class="totals-left">
                @if ($receipt->notes)
                    <div class="payment-section">
                        <div class="section-title">Notes</div>
                        <p style="font-size: 11px;">{!! nl2br(e($receipt->notes)) !!}</p>
                    </div>
                @endif
                <div class="mr-signature">
                    <div class="mr-signature-sign">
                        @if ($signature)
                            <img src="{{ $signature }}" alt="Manager signature" class="mr-signature-image">
                        @endif
                    </div>
                    <div class="mr-signature-line">Shipment Manager / Authorised Signature</div>
                    <div class="mr-signature-date">Date: {{ $receipt->created_at?->format('j F Y') }}</div>
                </div>
            </div>
            <div class="totals-right">
                <table class="totals-table" style="margin-left: auto;">
                    <tr class="grand-total">
                        <td class="total-label" style="color: white;">Total Paid:</td>
                        <td class="total-value" style="color: white;">{{ $receipt->formattedAmount() }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">Currency:</td>
                        <td class="total-value">{{ $receipt->currency }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer">
            <p style="font-size: 11px;">This receipt confirms that {{ $companyName }} received the payment above on
                {{ $receipt->paid_on->format('j F Y') }}.</p>
            <p style="font-size: 11px;">Issued{{ $receipt->issuer ? ' by '.$receipt->issuer->name : '' }} on
                {{ $receipt->created_at?->format('j F Y') }}. Quote receipt number {{ $receipt->receipt_number }} in any enquiry.</p>
            <p style="font-weight: bold">Thank you for choosing {{ $companyName }}!</p>
        </div>
    </div>
</body>

</html>
