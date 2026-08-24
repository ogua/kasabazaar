@php
    $money = fn ($amount) => ($amount < 0 ? '(' : '').'$'.number_format(abs((float) $amount), 2).($amount < 0 ? ')' : '');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Balance Sheet — {{ $statement['year'] }}</title>
    @include('reports.partials.financial-statement-styles')
</head>

<body>
    @include('reports.partials.financial-statement-header', [
        'reportTitle' => 'Statement of Financial Position',
        'reportPeriod' => 'As at '.\Carbon\Carbon::parse($statement['as_of'])->format('F j, Y'),
    ])

    @unless ($statement['is_balanced'])
        {{-- Surfaced rather than suppressed: an imbalance means the underlying records
             do not fully articulate, and whoever signs this needs to know before it
             reaches a lender. --}}
        <div class="warning-note">
            <strong>This statement does not balance.</strong>
            Total assets differ from liabilities plus equity by {{ $money($statement['imbalance']) }}.
            This usually means an account has not yet been recorded for the year. Resolve it before
            issuing this statement to a third party.
        </div>
    @endunless

    <table class="statement">
        <thead>
            <tr>
                <th>&nbsp;</th>
                <th class="text-right amount">{{ $statement['year'] }} ({{ $statement['currency'] }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach ([
                ['Current Assets', $statement['assets']['current'], 'current_assets'],
                ['Non-Current Assets', $statement['assets']['fixed'], 'fixed_assets'],
            ] as [$heading, $groups, $totalKey])
                @include('reports.partials.financial-statement-section', [
                    'heading' => $heading,
                    'groups' => $groups,
                    'total' => $statement['totals'][$totalKey],
                    'money' => $money,
                ])
            @endforeach

            <tr class="grand-total">
                <td>Total Assets</td>
                <td class="text-right amount">{{ $money($statement['totals']['total_assets']) }}</td>
            </tr>

            @foreach ([
                ['Current Liabilities', $statement['liabilities']['current'], 'current_liabilities'],
                ['Non-Current Liabilities', $statement['liabilities']['long_term'], 'long_term_liabilities'],
            ] as [$heading, $groups, $totalKey])
                @include('reports.partials.financial-statement-section', [
                    'heading' => $heading,
                    'groups' => $groups,
                    'total' => $statement['totals'][$totalKey],
                    'money' => $money,
                ])
            @endforeach

            <tr class="total-row">
                <td>Total Liabilities</td>
                <td class="text-right amount">{{ $money($statement['totals']['total_liabilities']) }}</td>
            </tr>

            @include('reports.partials.financial-statement-section', [
                'heading' => 'Equity',
                'groups' => $statement['equity'],
                'total' => $statement['totals']['total_equity'],
                'money' => $money,
            ])

            <tr class="grand-total">
                <td>Total Liabilities &amp; Equity</td>
                <td class="text-right amount">{{ $money($statement['totals']['liabilities_and_equity']) }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.financial-statement-signoff')
</body>

</html>
