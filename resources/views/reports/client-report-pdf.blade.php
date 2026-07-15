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

        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .stat-box {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
        }

        .stat-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            margin-top: 4px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0 8px;
            border-bottom: 1px solid #333;
            padding-bottom: 4px;
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
        <h2>{{ strtoupper($title) }}</h2>
        @if (! empty($startDate) || ! empty($endDate))
            <p>Period: {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('M d, Y') : 'Start' }}
                &mdash; {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('M d, Y') : 'Present' }}</p>
        @endif
        <p>Generated: {{ now()->format('M d, Y H:i:s') }}</p>
    </div>

    @if ($reportType === 'client_summary' || $reportType === 'top_clients')
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th class="text-right">Shipments</th>
                    <th class="text-right">Revenue (USD)</th>
                    <th class="text-right">Paid (USD)</th>
                    <th class="text-right">Balance (USD)</th>
                    <th>Payment Status Mix</th>
                    <th>Shipping Status Mix</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $row)
                    <tr>
                        <td>{{ $row['name'] }}<br><span style="font-size: 10px; color: #666;">{{ $row['phone'] }}</span></td>
                        <td class="text-right">{{ $row['shipment_count'] }}</td>
                        <td class="text-right">${{ number_format($row['total_usd'], 2) }}</td>
                        <td class="text-right positive">${{ number_format($row['paid_usd'], 2) }}</td>
                        <td class="text-right {{ $row['balance_usd'] > 0 ? 'negative' : 'positive' }}">${{ number_format($row['balance_usd'], 2) }}</td>
                        <td style="font-size: 10px;">
                            @foreach ($row['payment_status_counts'] as $status => $count)
                                @if ($count > 0)
                                    {{ ucfirst($status) }}: {{ $count }}&nbsp;
                                @endif
                            @endforeach
                        </td>
                        <td style="font-size: 10px;">
                            @foreach ($row['shipping_status_counts'] as $status => $count)
                                @if ($count > 0)
                                    {{ ucfirst($status) }}: {{ $count }}&nbsp;
                                @endif
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="stats-grid" style="margin-top: 20px;">
            <div class="stat-box">
                <div class="stat-label">Clients</div>
                <div class="stat-value">{{ $data->count() }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">${{ number_format($data->sum('total_usd'), 2) }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Collected</div>
                <div class="stat-value">${{ number_format($data->sum('paid_usd'), 2) }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Outstanding</div>
                <div class="stat-value">${{ number_format($data->sum('balance_usd'), 2) }}</div>
            </div>
        </div>
    @elseif ($reportType === 'new_clients')
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th class="text-right">Shipments Since</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $client)
                    <tr>
                        <td>{{ $client->name }}</td>
                        <td>{{ $client->phone }}</td>
                        <td>{{ $client->email }}</td>
                        <td>{{ $client->created_at?->format('M d, Y') }}</td>
                        <td class="text-right">{{ $client->shipments_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="stats-grid" style="margin-top: 20px;">
            <div class="stat-box">
                <div class="stat-label">New Clients</div>
                <div class="stat-value">{{ $data->count() }}</div>
            </div>
        </div>
    @elseif ($reportType === 'status_breakdown')
        @php $stats = $data->first(); @endphp

        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total Clients</div>
                <div class="stat-value">{{ $stats['total_clients'] }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Shipments</div>
                <div class="stat-value">{{ $stats['total_shipments'] }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">${{ number_format($stats['total_revenue_usd'], 2) }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Outstanding</div>
                <div class="stat-value {{ $stats['total_outstanding_usd'] > 0 ? 'negative' : 'positive' }}">
                    ${{ number_format($stats['total_outstanding_usd'], 2) }}
                </div>
            </div>
        </div>

        <div class="section-title">Shipping Status Breakdown</div>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">%</th>
                    <th class="text-right">Value (USD)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stats['shipping_status_breakdown'] as $row)
                    <tr>
                        <td>{{ ucfirst($row['label']) }}</td>
                        <td class="text-right">{{ $row['count'] }}</td>
                        <td class="text-right">{{ $row['percentage'] }}%</td>
                        <td class="text-right">${{ number_format($row['total_usd'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">Payment Status Breakdown</div>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">%</th>
                    <th class="text-right">Value (USD)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stats['payment_status_breakdown'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="text-right">{{ $row['count'] }}</td>
                        <td class="text-right">{{ $row['percentage'] }}%</td>
                        <td class="text-right">${{ number_format($row['total_usd'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>

</html>
