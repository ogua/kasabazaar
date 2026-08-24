@php
    $money = fn ($amount) => '$'.number_format((float) $amount, 2);
    $outstanding = (float) $statement['totals']['outstanding'];
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Accounts Receivable — {{ $statement['year'] }}</title>
    @include('reports.partials.financial-statement-styles')
</head>

<body>
    @include('reports.partials.financial-statement-header', [
        'reportTitle' => 'Schedule of Accounts Receivable',
        'reportPeriod' => 'As at '.\Carbon\Carbon::parse($statement['as_of'])->format('F j, Y'),
    ])

    <div class="section-heading">Ageing Summary</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 46%;">Days Outstanding</th>
                <th class="text-right" style="width: 14%;">Shipments</th>
                <th class="text-right" style="width: 22%;">Amount ({{ $statement['currency'] }})</th>
                <th class="text-right" style="width: 18%;">% of Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($statement['aging'] as $bucket)
                <tr>
                    <td>{{ $bucket['bucket'] }} days</td>
                    <td class="text-right">{{ $bucket['count'] }}</td>
                    <td class="text-right">{{ $money($bucket['amount']) }}</td>
                    <td class="text-right">
                        {{ $outstanding > 0 ? number_format($bucket['amount'] / $outstanding * 100, 1) : '0.0' }}%
                    </td>
                </tr>
            @endforeach
            <tr class="grand-total">
                <td>Total Outstanding</td>
                <td class="text-right">{{ $statement['totals']['shipment_count'] }}</td>
                <td class="text-right">{{ $money($outstanding) }}</td>
                <td class="text-right">100.0%</td>
            </tr>
        </tbody>
    </table>

    <div class="section-heading" style="margin-top: 16px;">By Client</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 26%;">Client</th>
                <th class="text-right" style="width: 9%;">Shipments</th>
                <th class="text-right" style="width: 11%;">0–30</th>
                <th class="text-right" style="width: 11%;">31–60</th>
                <th class="text-right" style="width: 11%;">61–90</th>
                <th class="text-right" style="width: 11%;">90+</th>
                <th class="text-right" style="width: 13%;">Total</th>
                <th class="text-right" style="width: 8%;">Oldest</th>
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
                <tr class="empty-row">
                    <td colspan="8">Nothing outstanding at this date.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="basis-note" style="margin-top: 14px;">
        Balances are the unpaid portion of each shipment (total invoiced less amounts received) and are
        aged from the date the shipment was raised. Fully settled shipments are excluded.
        {{ $statement['totals']['client_count'] }} client(s) had a balance outstanding at this date.
    </div>

    @include('reports.partials.financial-statement-signoff')
</body>

</html>
