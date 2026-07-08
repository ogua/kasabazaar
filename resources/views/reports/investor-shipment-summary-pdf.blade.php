<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shipment Performance Summary</title>
</head>
<body>
    <div class="container">
        @include('pdf.partials.investment-pdf-header', [
            'docTitle' => 'Shipment Performance Summary',
            'docSubtitle' => \Carbon\Carbon::parse($summary['start_date'])->format('M d, Y').' - '.\Carbon\Carbon::parse($summary['end_date'])->format('M d, Y'),
        ])

        <div class="summary-grid" style="display: table; width: 100%; margin-bottom: 20px;">
            <div style="display: table-cell; border: 1px solid #ddd; padding: 10px; width: 33%;">
                <div style="font-size: 10px; color: #666;">Total Shipments</div>
                <div style="font-size: 18px; font-weight: bold;">{{ $summary['shipment_count'] }}</div>
            </div>
            <div style="display: table-cell; border: 1px solid #ddd; padding: 10px; width: 33%;">
                <div style="font-size: 10px; color: #666;">Total Revenue (USD)</div>
                <div style="font-size: 18px; font-weight: bold;">${{ number_format($summary['total_revenue_usd'], 2) }}</div>
            </div>
            <div style="display: table-cell; border: 1px solid #ddd; padding: 10px; width: 33%;">
                <div style="font-size: 10px; color: #666;">Average Shipment Value</div>
                <div style="font-size: 18px; font-weight: bold;">${{ number_format($summary['average_value_usd'], 2) }}</div>
            </div>
        </div>

        <div class="section">
            <h2>Monthly Trend</h2>
            <table>
                <tr>
                    <th>Month</th>
                    <th class="text-right">Shipments</th>
                    <th class="text-right">Revenue (USD)</th>
                </tr>
                @foreach ($summary['by_month'] as $row)
                    <tr>
                        <td>{{ $row['month'] }}</td>
                        <td class="text-right">{{ $row['shipment_count'] }}</td>
                        <td class="text-right">${{ number_format($row['revenue_usd'], 2) }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

        <div class="section">
            <h2>By Status</h2>
            <table>
                <tr>
                    <th>Status</th>
                    <th class="text-right">Count</th>
                </tr>
                @foreach ($summary['by_status'] as $row)
                    <tr>
                        <td>{{ $row['status'] }}</td>
                        <td class="text-right">{{ $row['count'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</body>
</html>
