<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expense Report</title>
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
    <h1>Expense Report</h1>
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
            <div class="value" style="color: {{ $summary['growth_percent'] >= 0 ? 'red' : 'green' }}">
                {{ number_format(abs($summary['growth_percent']), 1) }}%
            </div>
        </div>
        <div class="summary-card">
            <div class="label">Total Expenses</div>
            <div class="value">{{ $summary['total_count'] }}</div>
        </div>
    </div>

    <h2>Expenses by Category</h2>
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

    <h2>Detailed Expenses</h2>
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
                <th>Stage</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
                <tr>
                    <td style="font-size: 10px;">{{ $expense['reference'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($expense['date'])->format('M d, Y') }}</td>
                    <td>{{ $expense['category'] }}</td>
                    <td>{{ Str::limit($expense['description'] ?? 'N/A', 30) }}</td>
                    <td class="text-right">${{ number_format($expense['amount_usd'], 2) }}</td>
                    <td class="text-right">₵{{ number_format($expense['amount_ghs'], 2) }}</td>
                    <td style="font-size: 10px;">{{ $expense['shipment_ref'] }}</td>
                    <td>{{ $expense['expense_stage'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
