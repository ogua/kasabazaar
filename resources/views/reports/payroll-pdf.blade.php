<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll Report</title>
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
    <h1>Payroll Report</h1>
    <p>Period: {{ \Carbon\Carbon::parse($summary['start_date'])->format('M d, Y') }} - {{ \Carbon\Carbon::parse($summary['end_date'])->format('M d, Y') }}</p>
    <p>Generated: {{ now()->format('M d, Y h:i A') }}</p>

    <h2>Summary</h2>
    <div class="summary-grid">
        <div class="summary-card">
            <div class="label">Period Total</div>
            <div class="value">₵{{ number_format($summary['this_month'], 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Previous Period</div>
            <div class="value">₵{{ number_format($summary['last_month'], 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Growth</div>
            <div class="value" style="color: {{ $summary['growth_percent'] >= 0 ? 'red' : 'green' }}">
                {{ number_format(abs($summary['growth_percent']), 1) }}%
            </div>
        </div>
        <div class="summary-card">
            <div class="label">Employees Paid</div>
            <div class="value">{{ $summary['total_employees'] }}</div>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="label">Average Salary</div>
            <div class="value">₵{{ number_format($summary['avg_salary'], 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Deductions</div>
            <div class="value" style="color: red;">₵{{ number_format($summary['total_deductions'], 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Bonuses</div>
            <div class="value" style="color: green;">₵{{ number_format($summary['total_bonuses'], 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Entries</div>
            <div class="value">{{ $summary['total_count'] }}</div>
        </div>
    </div>

    <h2>Payroll by Status</h2>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th class="text-right">Count</th>
                <th class="text-right">Total</th>
                <th class="text-right">Average</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary['by_status'] as $status => $data)
                <tr>
                    <td>{{ ucfirst($status) }}</td>
                    <td class="text-right">{{ $data['count'] }}</td>
                    <td class="text-right">₵{{ number_format($data['total'], 2) }}</td>
                    <td class="text-right">₵{{ number_format($data['avg'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Detailed Payroll Entries</h2>
    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Period</th>
                <th>Pay Date</th>
                <th class="text-right">Gross</th>
                <th class="text-right">Deductions</th>
                <th class="text-right">Bonuses</th>
                <th class="text-right">Net Salary</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payrolls as $payroll)
                <tr>
                    <td>{{ $payroll['employee'] }}</td>
                    <td style="font-size: 10px;">{{ $payroll['period'] }}</td>
                    <td>{{ $payroll['pay_date'] ? \Carbon\Carbon::parse($payroll['pay_date'])->format('M d, Y') : 'N/A' }}</td>
                    <td class="text-right">₵{{ number_format($payroll['gross_salary'], 2) }}</td>
                    <td class="text-right" style="color: red;">₵{{ number_format($payroll['deductions'], 2) }}</td>
                    <td class="text-right" style="color: green;">₵{{ number_format($payroll['bonuses'], 2) }}</td>
                    <td class="text-right"><strong>₵{{ number_format($payroll['net_salary'], 2) }}</strong></td>
                    <td>{{ $payroll['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
