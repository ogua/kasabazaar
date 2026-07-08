<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Income Summary</title>
</head>
<body>
    <div class="container">
        @include('pdf.partials.investment-pdf-header', [
            'docTitle' => 'Income Summary',
            'docSubtitle' => \Carbon\Carbon::parse($summary['start_date'])->format('M d, Y').' - '.\Carbon\Carbon::parse($summary['end_date'])->format('M d, Y'),
        ])

        <div class="summary-grid" style="display: table; width: 100%; margin-bottom: 20px;">
            <div style="display: table-cell; border: 1px solid #ddd; padding: 10px; width: 33%;">
                <div style="font-size: 10px; color: #666;">Total Income (USD)</div>
                <div style="font-size: 18px; font-weight: bold;">${{ number_format($summary['total_usd'], 2) }}</div>
            </div>
            <div style="display: table-cell; border: 1px solid #ddd; padding: 10px; width: 33%;">
                <div style="font-size: 10px; color: #666;">Total Income (GHS)</div>
                <div style="font-size: 18px; font-weight: bold;">₵{{ number_format($summary['total_ghs'], 2) }}</div>
            </div>
            <div style="display: table-cell; border: 1px solid #ddd; padding: 10px; width: 33%;">
                <div style="font-size: 10px; color: #666;">Number of Entries</div>
                <div style="font-size: 18px; font-weight: bold;">{{ $summary['count'] }}</div>
            </div>
        </div>

        <div class="section">
            <h2>Income by Category</h2>
            <table>
                <tr>
                    <th>Category</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">Total (USD)</th>
                    <th class="text-right">Total (GHS)</th>
                </tr>
                @foreach ($summary['by_category'] as $row)
                    <tr>
                        <td>{{ $row['category'] }}</td>
                        <td class="text-right">{{ $row['count'] }}</td>
                        <td class="text-right">${{ number_format($row['total_usd'], 2) }}</td>
                        <td class="text-right">₵{{ number_format($row['total_ghs'], 2) }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</body>
</html>
