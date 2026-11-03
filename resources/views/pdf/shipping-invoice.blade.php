@php
    $forPdf ??= false;

    $money = fn ($amount): string => '$'.number_format((float) $amount, 2);
    $embed = fn (string $path): ?string => file_exists(public_path($path))
        ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path($path)))
        : null;

    $logo = $embed('images/kasarose-logo.png');
    $signature = $embed('images/shipping-signature.png');

    $total = (float) $shipping->total;
    $amountPaid = (float) $shipping->payments->sum('amount');
    $balance = max(0, $total - $amountPaid);
    $fullyPaid = $amountPaid > 0 && $balance <= 0;

    $invoiceDate = $shipping->created_at->format('j F Y');
    $companyName = config('group.company.name');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $shipping->shipping_reference }}</title>
    <style>
        {{-- dompdf only: table layout, no flex/grid. The PDF repeats the header and footer on
             every page (position: fixed inside the @page margins); the browser view shows one sheet. --}}
        @page {
            margin: 96pt 36pt 50pt 36pt;
        }

        {{-- Not "*": dompdf puts the @page margins on <html>, and a universal reset would zero them. --}}
        body,
        div,
        p,
        table,
        th,
        td,
        img {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9.5pt;
            line-height: 1.35;
            color: #1f2937;
            background: #fff;
        }

        .glyph {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            vertical-align: top;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Header */
        .top-bar {
            height: 9pt;
            background: #003151;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo {
            width: 76pt;
        }

        .invoice-title {
            font-size: 22pt;
            font-weight: bold;
            color: #003151;
            line-height: 1;
            margin-bottom: 6pt;
        }

        .invoice-number {
            font-size: 9pt;
            font-weight: bold;
            color: #6b7280;
        }

        .invoice-number span {
            color: #374151;
            margin-left: 3pt;
        }

        .invoice-date {
            font-size: 9pt;
            color: #6b7280;
            margin-top: 3pt;
        }

        .header-rule {
            border-bottom: 1.5pt solid #A0043C;
            margin-top: 8pt;
        }

        /* Footer */
        .page-footer {
            font-size: 8pt;
            color: #6b7280;
        }

        /* Sections */
        .section-title {
            font-size: 11.5pt;
            font-weight: bold;
            color: #A0043C;
            text-transform: uppercase;
            margin: 10pt 0 8pt;
        }

        .label {
            font-size: 7.5pt;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 3pt;
        }

        .value {
            font-size: 9.5pt;
            color: #1f2937;
        }

        .value.small {
            font-size: 8pt;
        }

        .cards {
            border-collapse: separate;
            border-spacing: 0;
            page-break-inside: avoid;
        }

        .card {
            border-radius: 7pt;
            padding: 10pt 13pt;
        }

        .card-light {
            background: #e8eff5;
        }

        .card-grey {
            background: #f3f4f6;
        }

        .card-navy {
            background: #003151;
            color: #fff;
        }

        .card-navy .label {
            color: #fff;
        }

        .card-navy .value {
            color: #fff;
        }

        .card-gap {
            width: 12pt;
        }

        .fields td {
            padding-bottom: 9pt;
        }

        .fields tr:last-child td {
            padding-bottom: 0;
        }

        /* Receivers */
        .receiver {
            margin-bottom: 16pt;
            page-break-inside: avoid;
        }

        .receiver-head {
            background: #003151;
            color: #fff;
            font-size: 10.5pt;
            font-weight: bold;
            padding: 8pt 13pt;
            border-radius: 7pt 7pt 0 0;
        }

        .receiver-head .sep {
            margin: 0 5pt;
        }

        .receiver-meta {
            font-size: 8pt;
            color: #6b7280;
            padding: 6pt 13pt 0;
        }

        .receiver-meta .dot {
            margin: 0 4pt;
        }

        .items-wrap {
            padding: 10pt 7pt 0;
        }

        .items th {
            background: #e8eff5;
            color: #003151;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6pt 7pt;
        }

        .items td {
            padding: 6pt 7pt;
            border-bottom: 0.75pt solid #e5e7eb;
            font-size: 9pt;
        }

        .items tr {
            page-break-inside: avoid;
        }

        .col-qty {
            width: 60pt;
        }

        .col-charge {
            width: 90pt;
        }

        .note {
            background: #fffbeb;
            border-left: 3pt solid #f59e0b;
            border-radius: 4pt;
            padding: 8pt 11pt;
            margin-bottom: 10pt;
        }

        .note .label {
            color: #b45309;
        }

        /* Charges & payment */
        .charges td {
            padding: 4.5pt 0;
            font-size: 9pt;
            color: #6b7280;
        }

        .charges td.text-right {
            color: #1f2937;
            font-weight: bold;
        }

        .charges .total-row td {
            border-top: 0.75pt solid #003151;
            padding-top: 8pt;
            font-size: 11.5pt;
            font-weight: bold;
            color: #003151;
        }

        .charges .total-row td.text-right {
            color: #003151;
        }

        .charges .total-row.paid td.text-right {
            color: #16a34a;
        }

        .charges .spacer td {
            padding: 2pt 0;
        }

        .charges .balance td.text-right {
            color: #dc2626;
        }

        .charges .paid-amount td.text-right {
            color: #16a34a;
        }

        .methods-title {
            font-size: 10.5pt;
            font-weight: bold;
            color: #003151;
            text-transform: uppercase;
            margin-bottom: 10pt;
        }

        .method-name {
            font-size: 9pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 3pt;
        }

        .method-line {
            font-size: 8pt;
            color: #374151;
            margin-bottom: 3pt;
        }

        .track-box {
            display: block;
            text-decoration: none;
            text-align: center;
            background: #fdf2f6;
            border: 1pt solid #A0043C;
            border-radius: 7pt;
            padding: 7pt;
            margin-top: 10pt;
            page-break-inside: avoid;
        }

        .track-label {
            font-size: 8pt;
            font-weight: bold;
            color: #003151;
            text-transform: uppercase;
        }

        .track-number {
            font-size: 16pt;
            font-weight: bold;
            color: #A0043C;
            margin-top: 3pt;
        }

        .signature {
            width: 52%;
            margin-top: 10pt;
            page-break-inside: avoid;
        }

        .signature-image {
            max-height: 28pt;
            margin-top: 2pt;
        }

        .signature-line {
            border-bottom: 0.75pt solid #d1d5db;
            height: 30pt;
        }

        .signature-image + .signature-line {
            height: 2pt;
        }

        .signature-date {
            font-size: 8pt;
            color: #1f2937;
            margin-top: 4pt;
        }

        .insurance-box {
            border-radius: 7pt;
            padding: 7pt 11pt;
            margin-top: 8pt;
            page-break-inside: avoid;
        }

        .insurance-box.declined {
            background: #fef2f2;
            border: 1pt solid #dc2626;
        }

        .insurance-box.accepted {
            background: #f0fdf4;
            border: 1pt solid #16a34a;
        }

        .insurance-title {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4pt;
        }

        .declined .insurance-title {
            color: #b91c1c;
        }

        .accepted .insurance-title {
            color: #166534;
        }

        .insurance-box p {
            font-size: 7.5pt;
            color: #374151;
            margin-bottom: 2pt;
        }

        .thanks {
            text-align: center;
            font-size: 8.5pt;
            font-weight: bold;
            font-style: italic;
            color: #003151;
            margin-top: 5pt;
        }

        @if ($forPdf)
            .page-header {
                position: fixed;
                top: -96pt;
                left: 0;
                right: 0;
            }

            .top-bar {
                margin: 0 -36pt 14pt;
            }

            .page-footer {
                position: fixed;
                bottom: -32pt;
                left: 0;
                right: 0;
            }
        @else
            @media screen {
                html {
                    background: #e5e7eb;
                }

                body {
                    max-width: 794px;
                    margin: 24px auto;
                    padding: 0 48px 32px;
                    box-shadow: 0 4px 24px rgba(0, 0, 0, .08);
                }

                .top-bar {
                    margin: 0 -48px 18px;
                }
            }

            @media screen and (max-width: 640px) {
                body {
                    margin: 0;
                    padding: 0 16px 24px;
                }

                .top-bar {
                    margin: 0 -16px 14px;
                }

                .cards,
                .cards tbody,
                .cards tr,
                .cards td {
                    display: block;
                    width: 100% !important;
                }

                .card-gap {
                    height: 10px;
                }

                .signature {
                    width: 100%;
                }
            }

            @media print {
                .top-bar {
                    margin: 0 0 14pt;
                }
            }

            .page-footer {
                margin-top: 18pt;
            }
        @endif
    </style>
</head>

<body>
    <div class="page-header">
        <div class="top-bar"></div>
        <table class="header-table">
            <tr>
                <td>
                    @if ($logo)
                        <img src="{{ $logo }}" alt="{{ $companyName }}" class="logo">
                    @endif
                </td>
                <td class="text-right">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-number">INVOICE NO.<span>{{ $shipping->shipping_reference }}</span></div>
                    <div class="invoice-date">{{ $invoiceDate }}</div>
                </td>
            </tr>
        </table>
        <div class="header-rule"></div>
    </div>

    @if ($forPdf)
        <div class="page-footer">{{ $companyName }} <span class="dot">&bull;</span> USA to Ghana</div>
    @endif

    <!-- Sender & Shipment -->
    <div class="section-title">Sender &amp; Shipment</div>
    <table class="cards">
        <tr>
            <td class="card card-light" style="width: 62%;">
                <table class="fields">
                    <tr>
                        <td style="width: 50%;">
                            <div class="label">Sender</div>
                            <div class="value">{{ $shipping->client?->name ?: '—' }}</div>
                        </td>
                        <td>
                            <div class="label">Email</div>
                            <div class="value small">{{ $shipping->client?->email ?: '—' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Phone</div>
                            <div class="value">{{ $shipping->client?->phone ?: '—' }}</div>
                        </td>
                        <td>
                            <div class="label">Address</div>
                            <div class="value small">{!! $shipping->client?->address ? nl2br(e($shipping->client->address)) : '—' !!}</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="card-gap"></td>
            <td class="card card-navy">
                <table class="fields">
                    <tr>
                        <td>
                            <div class="label">Route</div>
                            <div class="value">{{ $shipping->origin_branch_id }} <span class="glyph">&rarr;</span> {{ $shipping->destination_branch_id }}</div>
                        </td>
                    </tr>
                    @if ($shipping->estimated_delivery_date)
                        <tr>
                            <td>
                                <div class="label">Estimated Delivery</div>
                                <div class="value">{{ \Carbon\Carbon::parse($shipping->estimated_delivery_date)->format('j F Y') }}</div>
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Receivers & Shipping Items -->
    @if ($shipping->receivers->isNotEmpty())
        <div class="section-title">Receivers &amp; Shipping Items</div>

        @foreach ($shipping->receivers as $receiverIndex => $receiver)
            @php
                $location = collect([$receiver->mcountry?->name, $receiver->mstate?->name, $receiver->mcity?->name])
                    ->filter()
                    ->implode(', ');
            @endphp
            <div class="receiver">
                <div class="receiver-head">
                    RECEIVER {{ $receiverIndex + 1 }}<span class="sep">|</span>{{ $receiver->receiver_name }}
                </div>
                @if ($receiver->receiver_phone || $location)
                    <div class="receiver-meta">
                        {{ $receiver->receiver_phone }}@if ($receiver->receiver_phone && $location)<span class="dot">&bull;</span>@endif{{ $location }}
                    </div>
                @endif

                @if ($receiver->items->isNotEmpty())
                    <div class="items-wrap">
                    <table class="items">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="col-qty text-center">Qty</th>
                                <th class="col-charge text-right">Charge</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($receiver->items as $item)
                                <tr>
                                    <td>{{ $item->product?->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-right">{{ $money($item->item_cost) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>
        @endforeach
    @endif

    @if ($shipping->client_note)
        <div class="note">
            <div class="label">Special note from client</div>
            <div class="value small">{{ $shipping->client_note }}</div>
        </div>
    @endif

    @if ($shipping->description)
        <div class="note">
            <div class="label">Shipment description</div>
            <div class="value small">{{ $shipping->description }}</div>
        </div>
    @endif

    <!-- Charges & Payment -->
    <div class="section-title">Charges &amp; Payment</div>
    <table class="cards">
        <tr>
            <td class="card card-light" style="width: 47%;">
                <table class="charges">
                    <tr>
                        <td>Item / shipping charges</td>
                        <td class="text-right">{{ $money($shipping->shipping_cost) }}</td>
                    </tr>
                    @if ((float) $shipping->discount > 0)
                        <tr>
                            <td>Discount</td>
                            <td class="text-right">&ndash; {{ $money($shipping->discount) }}</td>
                        </tr>
                    @endif
                    @if ($shipping->insurance_accepted)
                        <tr>
                            <td>Insurance</td>
                            <td class="text-right">{{ $money($shipping->insurance) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>VAT ({{ number_format((float) $shipping->vat_percentage, 1) }}%)</td>
                        <td class="text-right">{{ $money($shipping->vat) }}</td>
                    </tr>
                    <tr class="spacer">
                        <td colspan="2"></td>
                    </tr>
                    <tr class="total-row {{ $fullyPaid ? 'paid' : '' }}">
                        <td>{{ $fullyPaid ? 'TOTAL PAID' : 'TOTAL' }}</td>
                        <td class="text-right">{{ $money($total) }}</td>
                    </tr>
                    @unless ($fullyPaid)
                        <tr class="paid-amount">
                            <td>Amount paid</td>
                            <td class="text-right">{{ $money($amountPaid) }}</td>
                        </tr>
                        <tr class="balance">
                            <td>Balance due</td>
                            <td class="text-right">{{ $money($balance) }}</td>
                        </tr>
                    @endunless
                </table>
            </td>
            <td class="card-gap"></td>
            <td class="card card-grey">
                <div class="methods-title">Payment Methods</div>
                <table>
                    <tr>
                        <td style="width: 50%;">
                            <div class="method-name">Cash App</div>
                            <div class="method-line">Rose Adel <span class="dot">&bull;</span> $KasaBazaar1</div>
                            <div class="method-line">(317) 774-6913</div>
                        </td>
                        <td>
                            <div class="method-name">Zelle</div>
                            <div class="method-line">Kasabazaar</div>
                            <div class="method-line">(317) 774-6913</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($shipping->publicTrackingUrl())
        <a href="{{ $shipping->publicTrackingUrl() }}" target="_blank" class="track-box">
            <div class="track-label">Track your shipment</div>
            <div class="track-number">{{ $shipping->tracking_number ?: $shipping->shipping_reference }}</div>
        </a>
    @endif

    <div class="signature">
        <div class="label">Shipment Manager / Authorized Signature</div>
        @if ($signature)
            <img src="{{ $signature }}" alt="Manager signature" class="signature-image">
        @endif
        <div class="signature-line"></div>
        <div class="signature-date">Date: {{ now()->format('j F Y') }}</div>
    </div>

    @if ($shipping->insurance_accepted)
        <div class="insurance-box accepted">
            <div class="insurance-title">Insurance accepted &mdash; coverage {{ $money($shipping->insurance) }}</div>
            <p>All used and Non-Manufactured packed items are shipped without warranty.</p>
        </div>
    @else
        <div class="insurance-box declined">
            <div class="insurance-title">Insurance declined &mdash; no coverage for damage or loss</div>
            <p>Goods shipped without insurance cover does not guarantee full compensation in case of damage or loss.</p>
            <p>All used and Non-Manufactured packed items are shipped without warranty.</p>
        </div>
    @endif

    <div class="thanks">Thank you for choosing {{ $companyName }}!</div>

    @unless ($forPdf)
        <div class="page-footer">{{ $companyName }} <span class="dot">&bull;</span> USA to Ghana</div>
    @endunless
</body>

</html>
