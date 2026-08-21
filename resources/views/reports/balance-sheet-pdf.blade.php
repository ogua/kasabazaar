@php
    $money = fn ($amount) => ($amount < 0 ? '(' : '').'$'.number_format(abs((float) $amount), 2).($amount < 0 ? ')' : '');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Balance Sheet — {{ $statement['year'] }}</title>
    @include('reports.partials.financial-statement-header', [
        'reportTitle' => 'Statement of Financial Position',
        'reportPeriod' => 'As at '.\Carbon\Carbon::parse($statement['as_of'])->format('F j, Y'),
    ])
</head>

<body>
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

    <table>
        <thead>
            <tr>
                <th style="width: 70%;">&nbsp;</th>
                <th class="text-right">{{ $statement['year'] }} ({{ $statement['currency'] }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach ([
                ['Current Assets', $statement['assets']['current'], 'current_assets'],
                ['Non-Current Assets', $statement['assets']['fixed'], 'fixed_assets'],
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
                    <tr><td colspan="2" style="color:#888;">None recorded.</td></tr>
                @endforelse
                <tr class="total-row">
                    <td>Total {{ $heading }}</td>
                    <td class="text-right">{{ $money($statement['totals'][$totalKey]) }}</td>
                </tr>
            @endforeach

            <tr class="grand-total">
                <td>Total Assets</td>
                <td class="text-right">{{ $money($statement['totals']['total_assets']) }}</td>
            </tr>

            @foreach ([
                ['Current Liabilities', $statement['liabilities']['current'], 'current_liabilities'],
                ['Non-Current Liabilities', $statement['liabilities']['long_term'], 'long_term_liabilities'],
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
                    <tr><td colspan="2" style="color:#888;">None recorded.</td></tr>
                @endforelse
                <tr class="total-row">
                    <td>Total {{ $heading }}</td>
                    <td class="text-right">{{ $money($statement['totals'][$totalKey]) }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td>Total Liabilities</td>
                <td class="text-right">{{ $money($statement['totals']['total_liabilities']) }}</td>
            </tr>

            <tr><td colspan="2" class="section-heading">Equity</td></tr>
            @forelse ($statement['equity'] as $group)
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
                <tr><td colspan="2" style="color:#888;">None recorded.</td></tr>
            @endforelse
            <tr class="total-row">
                <td>Total Equity</td>
                <td class="text-right">{{ $money($statement['totals']['total_equity']) }}</td>
            </tr>

            <tr class="grand-total">
                <td>Total Liabilities &amp; Equity</td>
                <td class="text-right">{{ $money($statement['totals']['liabilities_and_equity']) }}</td>
            </tr>
        </tbody>
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
