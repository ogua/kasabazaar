@php
    $money = fn ($amount) => ($amount < 0 ? '(' : '').'$'.number_format(abs((float) $amount), 2).($amount < 0 ? ')' : '');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Profit &amp; Loss Statement — {{ $statement['year'] }}</title>
    @include('reports.partials.financial-statement-styles')
</head>

<body>
    @include('reports.partials.financial-statement-header', [
        'reportTitle' => 'Statement of Profit or Loss',
        'reportPeriod' => $statement['period']['label'],
    ])

    @php $unmapped = $statement['unmapped_categories'] ?? ['expenses' => [], 'incomes' => [], 'total' => 0]; @endphp

    @if ($unmapped['total'] > 0)
        <div class="warning-note">
            <strong>{{ $money($unmapped['total']) }} is in unmapped categories.</strong>
            The amounts below report under a catch-all account rather than their proper statement
            line, so cost of sales and the gross margin are understated:
            @foreach (array_merge($unmapped['expenses'], $unmapped['incomes']) as $category)
                {{ $category['name'] }} {{ $money($category['amount']) }}@if (! $loop->last); @endif
            @endforeach
        </div>
    @endif

    <table class="statement">
        <thead>
            <tr>
                <th>&nbsp;</th>
                <th class="text-right amount">{{ $statement['year'] }} ({{ $statement['currency'] }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach ([
                ['Revenue', $statement['revenue'], 'revenue'],
                ['Cost of Sales', $statement['cost_of_sales'], 'cost_of_sales'],
                ['Operating Expenses', $statement['operating_expenses'], 'operating_expenses'],
            ] as [$heading, $groups, $totalKey])
                <tr class="section-row">
                    <td colspan="2"><span class="section-heading">{{ $heading }}</span></td>
                </tr>

                @forelse ($groups as $group)
                    <tr class="line-row">
                        <td>{{ $group['statement_line'] }}</td>
                        <td class="text-right amount">{{ $money($group['amount']) }}</td>
                    </tr>
                    @foreach ($group['accounts'] as $account)
                        <tr class="detail-row">
                            <td>{{ $account['name'] }}</td>
                            <td class="text-right amount">{{ $money($account['amount']) }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr class="empty-row">
                        <td colspan="2">No amounts recorded for this year.</td>
                    </tr>
                @endforelse

                <tr class="total-row">
                    <td>Total {{ $heading }}</td>
                    <td class="text-right amount">{{ $money($statement['totals'][$totalKey]) }}</td>
                </tr>

                {{-- Gross profit closes out cost of sales before operating expenses open. --}}
                @if ($totalKey === 'cost_of_sales')
                    <tr class="grand-total">
                        <td>Gross Profit</td>
                        <td class="text-right amount">{{ $money($statement['totals']['gross_profit']) }}</td>
                    </tr>
                @elseif ($totalKey === 'operating_expenses')
                    <tr class="grand-total">
                        <td>Operating Profit</td>
                        <td class="text-right amount">{{ $money($statement['totals']['operating_profit']) }}</td>
                    </tr>
                @endif
            @endforeach

            {{-- Finance costs are shown as their own caption rather than folded into
                 operating expenses: this is the cost of the investor capital carried on
                 the balance sheet, and a lender reads it separately. --}}
            <tr class="section-row">
                <td colspan="2"><span class="section-heading">Finance Costs</span></td>
            </tr>
            @forelse ($statement['finance_costs'] as $group)
                <tr class="line-row">
                    <td>{{ $group['statement_line'] }}</td>
                    <td class="text-right amount">{{ $money($group['amount']) }}</td>
                </tr>
                @foreach ($group['accounts'] as $account)
                    <tr class="detail-row">
                        <td>{{ $account['name'] }}</td>
                        <td class="text-right amount">{{ $money($account['amount']) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr class="empty-row">
                    <td colspan="2">No finance costs recorded for this year.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td>Total Finance Costs</td>
                <td class="text-right amount">{{ $money($statement['totals']['finance_costs']) }}</td>
            </tr>

            <tr class="grand-total">
                <td>Profit for the Year</td>
                <td class="text-right amount">{{ $money($statement['totals']['net_profit']) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="metrics">
        <tr>
            <td class="metric-label">Gross margin</td>
            <td class="metric-value">{{ number_format($statement['totals']['gross_margin_percentage'], 2) }}%</td>
            <td class="metric-label">Net margin</td>
            <td class="metric-value">{{ number_format($statement['totals']['net_margin_percentage'], 2) }}%</td>
        </tr>
    </table>

    @include('reports.partials.financial-statement-signoff')
</body>

</html>
