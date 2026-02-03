<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            color: #e63946;
        }

        .header h2 {
            margin: 5px 0;
            font-size: 16px;
        }

        .shipment-block {
            margin-bottom: 25px;
            page-break-inside: avoid;
            border: 1px solid #ddd;
            padding: 15px;
        }

        .client-header {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .client-name {
            font-weight: bold;
            font-size: 14px;
        }

        .receiver-block {
            margin: 10px 0;
            padding: 10px;
            background-color: #fff;
            border-left: 3px solid #e63946;
        }

        .receiver-name {
            font-weight: bold;
        }

        .items-list {
            margin-left: 20px;
            margin-top: 5px;
        }

        .items-list li {
            margin: 3px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th {
            background-color: #333;
            color: white;
            padding: 8px;
            text-align: left;
        }

        table td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 2px solid #333;
        }

        .totals-section table {
            margin: 0;
        }

        .totals-section td {
            border: none;
            padding: 5px;
        }

        .positive {
            color: green;
        }

        .negative {
            color: red;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>KASA BAZAAR GLOBAL</h1>
        <h2>SHIPMENT AND DELIVERY DETAILS </h2>
        @if (isset($containerSequence))
            <p>Container: {{ $containerSequence }} SHIPMENT</p>
        @endif
        @if (isset($year))
            <p>Year: {{ $year }}</p>
        @endif
        <p>Generated: {{ now()->format('M d, Y H:i:s') }}</p>
    </div>

    @if ($reportType === 'by_container' || $reportType === 'by_year')
        {{-- Container/Year Report --}}
        @foreach ($data as $shipment)
            <div class="shipment-block">
                <div class="client-header">
                    <div class="client-name">CLIENT:
                        {{ strtoupper($shipment->client?->company_name ?? ($shipment->client?->name ?? 'N/A')) }}</div>
                    <div>Phone: {{ $shipment->client?->phone ?? 'N/A' }}</div>
                    <div>Reference: {{ $shipment->shipping_reference }}</div>
                    <div>Status: {{ $shipment->status?->getLabel() ?? 'Unknown' }}</div>
                    <div>Total: ${{ number_format($shipment->total ?? 0, 2) }} USD
                        @if($shipment->exchange_rate_at_shipment)
                            | GH₵{{ number_format($shipment->total_ghs ?? 0, 2) }}
                            (Rate: {{ number_format($shipment->exchange_rate_at_shipment, 4) }})
                        @endif
                    </div>
                </div>

                @foreach ($shipment->receivers as $receiver)
                    <div class="receiver-block">
                        <div class="receiver-name">RECEIVER: {{ strtoupper($receiver->receiver_name ?: 'SELF') }}</div>
                        @if ($receiver->receiver_phone)
                            <div>CONTACT: {{ $receiver->receiver_phone }}</div>
                        @endif
                        <div>LOCATION: {{ strtoupper($receiver->city ?? ($receiver->address ?? 'N/A')) }}
                            {{ strtoupper($receiver->mcountry?->name ?? '') }} {{ $receiver->city ? ',' : '' }}
                            {{ strtoupper($receiver->mstate?->name ?? '') }} {{ $receiver->mstate?->name ? ',' : '' }}
                            {{ strtoupper($receiver->mcity?->name ?? '') ?? '' }}
                        </div>
                        <div>ADDRESS: {{ strtoupper($receiver->address ?? 'N/A') }}
                        </div>

                        @if ($receiver->items && $receiver->items->count() > 0)
                            <div style="margin-top: 5px;"><strong>ITEMS</strong></div>
                            <ul class="items-list">
                                @foreach ($receiver->items as $item)
                                    <li>{{ strtoupper($item->product?->name ?? ($item->description ?? 'N/A')) }}
                                        @if ($item->quantity > 1)
                                            ({{ $item->quantity }})
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    @elseif($reportType === 'profit_loss')
        {{-- Profit/Loss Report --}}
        <table>
            <thead>
                <tr>
                    <th>Container</th>
                    <th class="text-right">Shipments</th>
                    <th class="text-right">Revenue (USD)</th>
                    <th class="text-right">Expenses (USD)</th>
                    <th class="text-right">Profit (USD)</th>
                    <th class="text-right">Margin %</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalRevenue = 0;
                    $totalExpenses = 0;
                    $totalProfit = 0;
                @endphp
                @foreach ($data as $row)
                    @php
                        $revenue = $row['revenue'] ?? 0;
                        $expenses = $row['expenses'] ?? 0;
                        $profit = $row['profit'] ?? 0;
                        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;

                        $totalRevenue += $revenue;
                        $totalExpenses += $expenses;
                        $totalProfit += $profit;
                    @endphp
                    <tr>
                        <td>{{ $row['container'] ?? 'N/A' }}</td>
                        <td class="text-right">{{ $row['shipment_count'] ?? 0 }}</td>
                        <td class="text-right">${{ number_format($revenue, 2) }}</td>
                        <td class="text-right">${{ number_format($expenses, 2) }}</td>
                        <td class="text-right {{ $profit >= 0 ? 'positive' : 'negative' }}">
                            ${{ number_format($profit, 2) }}</td>
                        <td class="text-right">{{ $margin }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-section">
            <table>
                <tr>
                    <td><strong>Total Revenue:</strong></td>
                    <td class="text-right">${{ number_format($totalRevenue, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Total Expenses:</strong></td>
                    <td class="text-right">${{ number_format($totalExpenses, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Net Profit:</strong></td>
                    <td class="text-right {{ $totalProfit >= 0 ? 'positive' : 'negative' }}">
                        <strong>${{ number_format($totalProfit, 2) }}</strong>
                    </td>
                </tr>
                <tr>
                    <td><strong>Overall Margin:</strong></td>
                    <td class="text-right">
                        <strong>{{ $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 2) : 0 }}%</strong>
                    </td>
                </tr>
            </table>
        </div>
    @endif
</body>

</html>
