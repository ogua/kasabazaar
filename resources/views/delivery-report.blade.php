<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Report - {{ $shipping->shipping_reference }}</title>
    @include('pdf.partials.investment-pdf-header', [
        'docTitle' => 'Delivery Report',
        'docSubtitle' => $shipping->shipping_reference,
    ])
    <style>
        .print-btn {
            margin-bottom: 16px;
        }

        @media print {
            .print-btn {
                display: none;
            }

            .receiver-block {
                page-break-inside: avoid;
            }
        }

        .ref-box {
            display: table;
            width: 100%;
            margin-bottom: 16px;
        }

        .ref-box .cell {
            display: table-cell;
            width: 33%;
        }

        .ref-box .cell .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }

        .ref-box .cell .value {
            font-size: 14px;
            font-weight: bold;
        }

        .receiver-block {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 20px;
        }

        .receiver-block h3 {
            color: #A0043C;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .signature-block {
            display: table;
            width: 100%;
            margin-top: 24px;
        }

        .signature-block .col {
            display: table-cell;
            width: 33%;
            padding-right: 12px;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 34px;
            margin-bottom: 4px;
        }

        .signature-label {
            font-size: 10px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <button class="print-btn" onclick="window.print()">Print</button>

        <div class="ref-box">
            <div class="cell">
                <div class="label">Sender</div>
                <div class="value">{{ $shipping->client?->name }}</div>
            </div>
            <div class="cell">
                <div class="label">Container</div>
                <div class="value">{{ $shipping->containerStatus?->container_ref ?? ($shipping->container_number ? 'CON'.$shipping->container_number : '—') }}</div>
            </div>
            <div class="cell">
                <div class="label">Assigned Agent</div>
                <div class="value">{{ $shipping->latestDelivery?->clearingAgent?->name ?? 'Not yet assigned' }}</div>
            </div>
        </div>

        @foreach ($shipping->receivers as $receiver)
            <div class="receiver-block">
                <h3>Receiver {{ $loop->iteration }}: {{ $receiver->receiver_name }}</h3>
                <p>Phone: {{ $receiver->receiver_phone ?? 'N/A' }}</p>
                <p>Address: {{ $receiver->address ? $receiver->address.', ' : '' }}{{ $receiver->mcity?->name }}{{ $receiver->mcity && $receiver->mstate ? ', ' : '' }}{{ $receiver->mstate?->name }}{{ $receiver->mstate ? ', ' : '' }}{{ $receiver->mcountry?->name }}</p>

                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($receiver->items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->product?->name }}</td>
                                <td>{{ $item->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="signature-block">
                    <div class="col">
                        <div class="signature-line"></div>
                        <div class="signature-label">Received By (Print Name)</div>
                    </div>
                    <div class="col">
                        <div class="signature-line"></div>
                        <div class="signature-label">Signature</div>
                    </div>
                    <div class="col">
                        <div class="signature-line"></div>
                        <div class="signature-label">Date</div>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="footer">
            Generated {{ now()->format('M d, Y g:ia') }}
        </div>
    </div>
</body>

</html>
