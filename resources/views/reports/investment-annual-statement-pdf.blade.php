<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Investment Statement {{ $statement->year }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 22px; margin-bottom: 4px; color: #A0043C; }
        h2 { font-size: 16px; margin-top: 20px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; background: #fdf2f6; }
        .updates-box { border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>KasaBazaar LLC d/b/a Rose Door to Door Shipping &amp; Delivery Services</h1>
    <p>Annual Investment Statement — {{ $statement->year }}</p>
    <p>Investor: {{ $investor->name }}</p>
    <p>Generated: {{ now()->format('M d, Y') }}</p>

    <h2>Investment Summary</h2>
    <table>
        <tr>
            <th>Investment</th>
            <th class="text-right">Opening Balance</th>
            <th class="text-right">Interest Credited</th>
            <th class="text-right">Closing Balance</th>
        </tr>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row['reference'] }}</td>
                <td class="text-right">${{ number_format($row['opening_balance'], 2) }}</td>
                <td class="text-right">${{ number_format($row['interest_credited'], 2) }}</td>
                <td class="text-right">${{ number_format($row['closing_balance'], 2) }}</td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td>Total Investment Value</td>
            <td></td>
            <td></td>
            <td class="text-right">${{ number_format($totalValue, 2) }}</td>
        </tr>
    </table>

    <h2>Significant Business Updates</h2>
    <div class="updates-box">
        {!! nl2br(e($statement->business_updates ?? 'No updates provided for this period.')) !!}
    </div>
</body>
</html>
