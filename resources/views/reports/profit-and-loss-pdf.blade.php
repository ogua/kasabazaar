@php
    $money = fn ($amount) => ($amount < 0 ? '(' : '').'$'.number_format(abs((float) $amount), 2).($amount < 0 ? ')' : '');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Profit &amp; Loss Statement — {{ $statement['year'] }}</title>
    @include('reports.partials.financial-statement-header', [
        'reportTitle' => 'Statement of Profit or Loss',
        'reportPeriod' => $statement['period']['label'],
    ])
</head>

<body>
    <table>
        <thead>
            <tr>
                <th style="width: 70%;">&nbsp;</th>
                <th class="text-right">{{ $statement['year'] }} ({{ $statement['currency'] }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach ([
                ['Revenue', $statement['revenue'], 'revenue'],
                ['Cost of Sales', $statement['cost_of_sales'], 'cost_of_sales'],
            ] as [$heading, $groups, $totalKey])
                <tr><td colspan="2" class="section-heading">{{ $heading }}</td></tr>

                @forelse ($groups as $group)
                    <tr class="line-row">
                        <td>{{ $group['statement_line'] }}</td>
                        <td class="text-right">{{ $money($group['amount']) }}</td>
                    </tr>
                    @foreach ($group['accounts'] as $account)
                        <tr class="detail-row">
                            <td>{{ $account['name'] }}</td>
                            <td class="text-right">{{ $money($account['amount']) }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="2" style="color:#888;">No amounts recorded for this year.</td></tr>
                @endforelse

                <tr class="total-row">
                    <td>Total {{ $heading }}</td>
                    <td class="text-right">{{ $money($statement['totals'][$totalKey]) }}</td>
                </tr>
            @endforeach

            <tr class="grand-total">
                <td>Gross Profit</td>
                <td class="text-right">{{ $money($statement['totals']['gross_profit']) }}</td>
            </tr>

            <tr><td colspan="2" class="section-heading">Operating Expenses</td></tr>
            @forelse ($statement['operating_expenses'] as $group)
                <tr class="line-row">
                    <td>{{ $group['statement_line'] }}</td>
                    <td class="text-right">{{ $money($group['amount']) }}</td>
                </tr>
                @foreach ($group['accounts'] as $account)
                    <tr class="detail-row">
                        <td>{{ $account['name'] }}</td>
                        <td class="text-right">{{ $money($account['amount']) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="2" style="color:#888;">No amounts recorded for this year.</td></tr>
            @endforelse
            <tr class="total-row">
                <td>Total Operating Expenses</td>
                <td class="text-right">{{ $money($statement['totals']['operating_expenses']) }}</td>
            </tr>

            <tr class="grand-total">
                <td>Operating Profit</td>
                <td class="text-right">{{ $money($statement['totals']['operating_profit']) }}</td>
            </tr>

            {{-- Finance costs are shown as their own caption rather than folded into
                 operating expenses: this is the cost of the investor capital carried on
                 the balance sheet, and a lender reads it separately. --}}
            <tr><td colspan="2" class="section-heading">Finance Costs</td></tr>
            @forelse ($statement['finance_costs'] as $group)
                <tr class="line-row">
                    <td>{{ $group['statement_line'] }}</td>
                    <td class="text-right">{{ $money($group['amount']) }}</td>
                </tr>
                @foreach ($group['accounts'] as $account)
                    <tr class="detail-row">
                        <td>{{ $account['name'] }}</td>
                        <td class="text-right">{{ $money($account['amount']) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="2" style="color:#888;">No finance costs recorded for this year.</td></tr>
            @endforelse
            <tr class="total-row">
                <td>Total Finance Costs</td>
                <td class="text-right">{{ $money($statement['totals']['finance_costs']) }}</td>
            </tr>

            <tr class="grand-total">
                <td>Profit for the Year</td>
                <td class="text-right">{{ $money($statement['totals']['net_profit']) }}</td>
            </tr>
        </tbody>
    </table>

    <table style="margin-top: 10px;">
        <tr>
            <td>Gross margin</td>
            <td class="text-right">{{ number_format($statement['totals']['gross_margin_percentage'], 2) }}%</td>
            <td>Net margin</td>
            <td class="text-right">{{ number_format($statement['totals']['net_margin_percentage'], 2) }}%</td>
        </tr>
    </table>

    <div class="signoff">
        <div class="signoff-col">
            <div class="signoff-line">Prepared by</div>
        </div>
        <div class="signoff-col" style="margin-left: 6%;">
            <div class="signoff-line">Approved by (Director)</div>
        </div>
    </div>
</body>

</html>
