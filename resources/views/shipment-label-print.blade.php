<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Label - {{ $shipping->shipping_reference }}</title>
    @include('pdf.partials.investment-pdf-header', [
        'docTitle' => 'Shipment Contents',
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
        }

        .qr-box {
            text-align: center;
            margin-top: 20px;
        }

        .qr-box img {
            width: 160px;
            height: 160px;
        }

        .qr-box .token {
            font-size: 10px;
            color: #666;
            word-break: break-all;
            margin-top: 6px;
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
    </style>
</head>

<body>
    <div class="container">
        <button class="print-btn" onclick="window.print()">Print</button>

        <div class="ref-box">
            <div class="cell">
                <div class="label">Tracking Number</div>
                <div class="value">{{ $shipping->tracking_number }}</div>
            </div>
            <div class="cell">
                <div class="label">Container</div>
                <div class="value">{{ $shipping->containerStatus?->container_ref ?? ($shipping->container_number ? 'CON'.$shipping->container_number : '—') }}</div>
            </div>
            <div class="cell">
                <div class="label">Status</div>
                <div class="value">{{ $shipping->containerStatus?->is_cleared ? 'Cleared' : 'Pending Clearance' }}</div>
            </div>
        </div>

        <div class="section">
            <h2>Contents</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @php $rowNum = 1; @endphp
                    @foreach ($shipping->receivers as $receiver)
                        @foreach ($receiver->items as $item)
                            <tr>
                                <td>{{ $rowNum++ }}</td>
                                <td>{{ $item->product?->name }}</td>
                                <td>{{ $item->quantity }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="qr-box">
            @php
                $qrCode = new \Endroid\QrCode\QrCode(route('shipment-label-lookup', $shipping->public_view_token));
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $result = $writer->write($qrCode);
            @endphp
            <img src="{{ $result->getDataUri() }}" alt="Scan to view contents">
            <div class="token">Scan to view this shipment's contents online</div>
        </div>

        <div class="footer">
            Generated {{ now()->format('M d, Y g:ia') }}
        </div>
    </div>
</body>

</html>
