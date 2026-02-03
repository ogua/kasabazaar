<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Income Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 24px; margin-bottom: 10px; }
        h2 { font-size: 18px; margin-top: 20px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .summary-card { border: 1px solid #ddd; padding: 10px; }
        .summary-card .label { font-size: 10px; color: #666; }
        .summary-card .value { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Income Report</h1>
    <p>Period: {{ \Carbon\Carbon::parse($summary['start_date'])->format('M d, Y') }} - {{ \Carbon\Carbon::parse($summary['end_date'])->format('M d, Y') }}</p>
    <p>Generated: {{ now()->format('M d, Y h:i A') }}</p>

    <h2>Summary</h2>
    <div class="summary-grid">
        <div class="summary-card">
            <div class="label">Period Total (GHS)</div>
            <div class="value">₵{{ number_format($summary['this_month_ghs'], 2) }}</div>
            <div class="label">${{ number_format($summary['this_month_usd'], 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Previous Period (GHS)</div>
            <div class="value">₵{{ number_format($summary['last_month_ghs'], 2) }}</div>
            <div class="label">${{ number_format($summary['last_month_usd'], 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Growth</div>
            <div class="value" style="color: {{ $summary['growth_percent'] >= 0 ? 'green' : 'red' }}">
                {{ number_format(abs($summary['growth_percent']), 1) }}%
            </div>
        </div>
        <div class="summary-card">
            <div class="label">Total Income</div>
            <div class="value">{{ $summary['total_count'] }}</div>
        </div>
    </div>

    <h2>Income by Category</h2>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th class="text-right">Count</th>
                <th class="text-right">Total (USD)</th>
                <th class="text-right">Total (GHS)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary['by_category'] as $category => $data)
                <tr>
                    <td>{{ $category }}</td>
                    <td class="text-right">{{ $data['count'] }}</td>
                    <td class="text-right">${{ number_format($data['total_usd'], 2) }}</td>
                    <td class="text-right">₵{{ number_format($data['total_ghs'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Income by Payment Method</h2>
    <table>
        <thead>
            <tr>
                <th>Payment Method</th>
                <th class="text-right">Count</th>
                <th class="text-right">Total (USD)</th>
                <th class="text-right">Total (GHS)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary['by_method'] as $method => $data)
                <tr>
                    <td>{{ ucfirst($method) }}</td>
                    <td class="text-right">{{ $data['count'] }}</td>
                    <td class="text-right">${{ number_format($data['total_usd'], 2) }}</td>
                    <td class="text-right">₵{{ number_format($data['total_ghs'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Detailed Income Records</h2>
    <table>
        <thead>
            <tr>
                <th>Reference</th>
                <th>Date</th>
                <th>Category</th>
                <th>Description</th>
                <th class="text-right">Amount (USD)</th>
                <th class="text-right">Amount (GHS)</th>
                <th>Shipment</th>
                <th>Method</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incomes as $income)
                <tr>
                    <td style="font-size: 10px;">{{ $income['reference'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($income['date'])->format('M d, Y') }}</td>
                    <td>{{ $income['category'] }}</td>
                    <td>{{ Str::limit($income['description'] ?? 'N/A', 30) }}</td>
                    <td class="text-right">${{ number_format($income['amount_usd'], 2) }}</td>
                    <td class="text-right">₵{{ number_format($income['amount_ghs'], 2) }}</td>
                    <td style="font-size: 10px;">{{ $income['shipment_ref'] }}</td>
                    <td>{{ ucfirst($income['payment_method']) }}</td>
                    <td>{{ $income['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
