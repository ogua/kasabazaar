@php
    $money = fn ($amount) => '$'.number_format((float) $amount, 2);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Accounts Receivable — {{ $statement['year'] }}</title>
    @include('reports.partials.financial-statement-header', [
        'reportTitle' => 'Schedule of Accounts Receivable',
        'reportPeriod' => 'As at '.\Carbon\Carbon::parse($statement['as_of'])->format('F j, Y'),
    ])
</head>

<body>
    <div class="section-heading">Ageing Summary</div>
    <table>
        <thead>
            <tr>
                <th>Days Outstanding</th>
                <th class="text-right">Shipments</th>
                <th class="text-right">Amount ({{ $statement['currency'] }})</th>
                <th class="text-right">% of Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($statement['aging'] as $bucket)
                <tr>
                    <td>{{ $bucket['bucket'] }} days</td>
                    <td class="text-right">{{ $bucket['count'] }}</td>
                    <td class="text-right">{{ $money($bucket['amount']) }}</td>
                    <td class="text-right">
                        {{ $statement['totals']['outstanding'] > 0
                            ? number_format($bucket['amount'] / $statement['totals']['outstanding'] * 100, 1)
                            : '0.0' }}%
                    </td>
                </tr>
            @endforeach
            <tr class="grand-total">
                <td>Total Outstanding</td>
                <td class="text-right">{{ $statement['totals']['shipment_count'] }}</td>
                <td class="text-right">{{ $money($statement['totals']['outstanding']) }}</td>
                <td class="text-right">100.0%</td>
            </tr>
        </tbody>
    </table>

    <div class="section-heading">By Client</div>
    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th class="text-right">Shipments</th>
                <th class="text-right">0–30</th>
                <th class="text-right">31–60</th>
                <th class="text-right">61–90</th>
                <th class="text-right">90+</th>
                <th class="text-right">Total</th>
                <th class="text-right">Oldest</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($statement['clients'] as $client)
                <tr>
                    <td>{{ $client['client'] }}</td>
                    <td class="text-right">{{ $client['shipment_count'] }}</td>
                    <td class="text-right">{{ $money($client['buckets']['0-30']) }}</td>
                    <td class="text-right">{{ $money($client['buckets']['31-60']) }}</td>
                    <td class="text-right">{{ $money($client['buckets']['61-90']) }}</td>
                    <td class="text-right {{ $client['buckets']['90+'] > 0 ? 'negative' : '' }}">
                        {{ $money($client['buckets']['90+']) }}
                    </td>
                    <td class="text-right"><strong>{{ $money($client['outstanding']) }}</strong></td>
                    <td class="text-right">{{ $client['oldest_days'] }}d</td>
                </tr>
            @empty
                <tr><td colspan="8" style="color:#888;">Nothing outstanding at this date.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="basis-note" style="margin-top: 12px;">
        Balances are the unpaid portion of each shipment (total invoiced less amounts received) and are
        aged from the date the shipment was raised. Fully settled shipments are excluded.
        {{ $statement['totals']['client_count'] }} client(s) had a balance outstanding at this date.
    </div>

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
