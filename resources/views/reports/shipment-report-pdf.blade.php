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
        @if (!empty($startDate) || !empty($endDate))
            <p>Period: {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('M d, Y') : 'Start' }}
                &mdash; {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('M d, Y') : 'Present' }}</p>
        @endif
        <p>Generated: {{ now()->format('M d, Y H:i:s') }}</p>
    </div>

    @if ($reportType === 'by_container' || $reportType === 'by_year' || $reportType === 'by_date_range' || $reportType === 'client_shipments')
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
        @php
            $grandRevenue = 0;
            $grandExpenses = 0;
            $grandProfit = 0;
            $grandPaid = 0;
            $grandBalance = 0;
        @endphp
        @foreach ($data as $row)
            @php
                $revenue = $row['revenue'] ?? 0;
                $expenses = $row['expenses'] ?? 0;
                $profit = $row['profit'] ?? 0;
                $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;
                $grandRevenue += $revenue;
                $grandExpenses += $expenses;
                $grandProfit += $profit;
                $containerPaid = collect($row['clients'] ?? [])->sum('paid');
                $containerBalance = collect($row['clients'] ?? [])->sum('balance');
                $grandPaid += $containerPaid;
                $grandBalance += $containerBalance;
            @endphp
            {{-- Container summary row --}}
            <table style="margin-bottom: 0;">
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
                    <tr style="background-color:#f0f0f0; font-weight:bold;">
                        <td>{{ $row['container'] ?? 'N/A' }}</td>
                        <td class="text-right">{{ $row['shipment_count'] ?? 0 }}</td>
                        <td class="text-right">${{ number_format($revenue, 2) }}</td>
                        <td class="text-right">${{ number_format($expenses, 2) }}</td>
                        <td class="text-right {{ $profit >= 0 ? 'positive' : 'negative' }}">${{ number_format($profit, 2) }}</td>
                        <td class="text-right">{{ $margin }}%</td>
                    </tr>
                </tbody>
            </table>
            {{-- Client breakdown --}}
            @if (!empty($row['clients']) && count($row['clients']) > 0)
                <table style="margin-top:0; margin-bottom:20px; font-size:11px;">
                    <thead>
                        <tr style="background-color:#555;">
                            <th style="padding-left:20px;">Client</th>
                            <th class="text-right">Shipments</th>
                            <th class="text-right">Total (USD)</th>
                            <th class="text-right">Paid (USD)</th>
                            <th class="text-right">Balance (USD)</th>
                            <th class="text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($row['clients'] as $client)
                            @php
                                $statusLabel = match($client['payment_status']) {
                                    'paid' => 'PAID',
                                    'partial' => 'PARTIAL',
                                    default => 'UNPAID',
                                };
                                $statusColor = match($client['payment_status']) {
                                    'paid' => 'green',
                                    'partial' => 'orange',
                                    default => 'red',
                                };
                            @endphp
                            <tr>
                                <td style="padding-left:20px;">
                                    <strong>{{ $client['name'] }}</strong>
                                    @if($client['phone'])
                                        &nbsp;{{ $client['phone'] }}
                                    @endif
                                </td>
                                <td class="text-right">{{ $client['shipment_count'] }}</td>
                                <td class="text-right">${{ number_format($client['total'], 2) }}</td>
                                <td class="text-right positive">${{ number_format($client['paid'], 2) }}</td>
                                <td class="text-right {{ $client['balance'] > 0 ? 'negative' : 'positive' }}">${{ number_format($client['balance'], 2) }}</td>
                                <td class="text-right" style="color:{{ $statusColor }}; font-weight:bold;">{{ $statusLabel }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach

        <div class="totals-section">
            <table>
                <tr>
                    <td><strong>Total Shipment Revenue:</strong></td>
                    <td class="text-right">${{ number_format($grandRevenue, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Total Expenses:</strong></td>
                    <td class="text-right">${{ number_format($grandExpenses, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Net Profit:</strong></td>
                    <td class="text-right {{ $grandProfit >= 0 ? 'positive' : 'negative' }}">
                        <strong>${{ number_format($grandProfit, 2) }}</strong>
                    </td>
                </tr>
                <tr>
                    <td><strong>Overall Margin:</strong></td>
                    <td class="text-right">
                        <strong>{{ $grandRevenue > 0 ? round(($grandProfit / $grandRevenue) * 100, 2) : 0 }}%</strong>
                    </td>
                </tr>
                <tr style="border-top: 2px solid #333;">
                    <td><strong>Total Collected from Clients:</strong></td>
                    <td class="text-right positive"><strong>${{ number_format($grandPaid, 2) }}</strong></td>
                </tr>
                <tr>
                    <td><strong>Total Outstanding Balance:</strong></td>
                    <td class="text-right {{ $grandBalance > 0 ? 'negative' : 'positive' }}">
                        <strong>${{ number_format($grandBalance, 2) }}</strong>
                    </td>
                </tr>
            </table>
        </div>
    @elseif($reportType === 'debtors')
        {{-- Debtors Report --}}
        @php
            $grandDebtors = 0;
            $grandOutstanding = 0;
        @endphp
        @foreach ($data as $row)
            @php
                $grandDebtors += $row['debtor_count'] ?? 0;
                $grandOutstanding += $row['total_outstanding'] ?? 0;
            @endphp
            {{-- Container summary row --}}
            <table style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th>Container</th>
                        <th class="text-right">Debtors</th>
                        <th class="text-right">Total Outstanding (USD)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background-color:#f0f0f0; font-weight:bold;">
                        <td>{{ $row['container'] ?? 'N/A' }}</td>
                        <td class="text-right">{{ $row['debtor_count'] ?? 0 }}</td>
                        <td class="text-right negative">${{ number_format($row['total_outstanding'] ?? 0, 2) }}</td>
                    </tr>
                </tbody>
            </table>
            {{-- Debtor breakdown --}}
            @if (!empty($row['clients']) && count($row['clients']) > 0)
                <table style="margin-top:0; margin-bottom:20px; font-size:11px;">
                    <thead>
                        <tr style="background-color:#555;">
                            <th style="padding-left:20px;">Client</th>
                            <th class="text-right">Shipments</th>
                            <th class="text-right">Total (USD)</th>
                            <th class="text-right">Paid (USD)</th>
                            <th class="text-right">Balance (USD)</th>
                            <th class="text-right">Days Outstanding</th>
                            <th class="text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($row['clients'] as $client)
                            @php
                                $statusLabel = $client['payment_status'] === 'partial' ? 'PARTIAL' : 'UNPAID';
                                $statusColor = $client['payment_status'] === 'partial' ? 'orange' : 'red';
                            @endphp
                            <tr>
                                <td style="padding-left:20px;">
                                    <strong>{{ $client['name'] }}</strong>
                                    @if($client['phone'])
                                        &nbsp;{{ $client['phone'] }}
                                    @endif
                                </td>
                                <td class="text-right">{{ $client['shipment_count'] }}</td>
                                <td class="text-right">${{ number_format($client['total'], 2) }}</td>
                                <td class="text-right positive">${{ number_format($client['paid'], 2) }}</td>
                                <td class="text-right negative">${{ number_format($client['balance'], 2) }}</td>
                                <td class="text-right">{{ $client['days_outstanding'] }}</td>
                                <td class="text-right" style="color:{{ $statusColor }}; font-weight:bold;">{{ $statusLabel }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach

        <div class="totals-section">
            <table>
                <tr>
                    <td><strong>Total Containers with Debtors:</strong></td>
                    <td class="text-right">{{ $data->count() }}</td>
                </tr>
                <tr>
                    <td><strong>Total Debtors:</strong></td>
                    <td class="text-right">{{ $grandDebtors }}</td>
                </tr>
                <tr style="border-top: 2px solid #333;">
                    <td><strong>Total Outstanding Balance:</strong></td>
                    <td class="text-right negative"><strong>${{ number_format($grandOutstanding, 2) }}</strong></td>
                </tr>
            </table>
        </div>
    @endif
</body>

</html>
