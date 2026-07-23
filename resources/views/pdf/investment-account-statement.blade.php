<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Investment Account Statement - {{ $investor->name }}</title>
    <style>
        .transactions-table {
            font-size: 9px;
        }

        .transactions-table th,
        .transactions-table td {
            padding: 4px 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        @include('pdf.partials.investment-pdf-header', [
            'docTitle' => 'Investment Account Statement',
            'docSubtitle' => $investor->name,
        ])

        <div class="section">
            <p>This statement reflects the full history of investments held by {{ $investor->name }} with KasaBazaar
                LLC d/b/a Rose Door to Door Shipping &amp; Delivery Services, as of {{ $asOfDate->format('F j, Y') }}.</p>
        </div>

        @foreach ($investments as $investment)
            @php $valuation = $valuations[$investment->id]; @endphp
            <div class="section">
                <h2>Investment {{ $investment->reference }}</h2>

                <table>
                    <tr>
                        <th>Principal (USD)</th>
                        <th>Principal (GHS)</th>
                        <th>Start Date</th>
                        <th>Status</th>
                    </tr>
                    <tr>
                        <td>${{ number_format($investment->principal_amount, 2) }}</td>
                        <td>₵{{ number_format($investment->principal_amount_ghs, 2) }}</td>
                        <td>{{ \Carbon\Carbon::parse($investment->start_date)->format('F j, Y') }}</td>
                        <td>{{ $investment->status->getLabel() }}</td>
                    </tr>
                </table>

                <table>
                    <tr>
                        <th>Payment Gateway</th>
                        <th>Payment Method</th>
                        <th>Payment Reference</th>
                    </tr>
                    <tr>
                        <td>{{ $investment->deposit_gateway ? ucfirst($investment->deposit_gateway) : '—' }}</td>
                        <td>{{ $investment->payment_method ? ucfirst($investment->payment_method) : '—' }}</td>
                        <td>{{ $investment->payment_reference ?? '—' }}</td>
                    </tr>
                </table>

                <div class="valuation-box">
                    <div class="row">
                        <div class="label">Interest Earned to Date</div>
                        <div class="value">${{ number_format($valuation['interest_earned_total'], 2) }}</div>
                    </div>
                    <div class="row">
                        <div class="label">Current Value</div>
                        <div class="value">${{ number_format($valuation['compounded_balance'], 2) }}</div>
                    </div>
                </div>

                <h2>Transaction History</h2>
                @if ($investment->transactions->isEmpty())
                    <p>No posted transactions yet.</p>
                @else
                    <table class="transactions-table">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Interest Period</th>
                            <th class="text-right">Days</th>
                            <th class="text-right">Rate</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Credit</th>
                            <th class="text-right">Balance</th>
                            <th>Description</th>
                        </tr>
                        @foreach ($investment->transactions as $transaction)
                            @php
                                $hasPeriod = $transaction->period_start && $transaction->period_end;
                                $daysHeld = $hasPeriod ? $transaction->period_start->diffInDays($transaction->period_end) + 1 : null;
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($transaction->date)->format('M j, Y') }}</td>
                                <td>{{ $transaction->type->getLabel() }}</td>
                                <td>{{ $hasPeriod ? $transaction->period_start->format('M j, Y').' – '.$transaction->period_end->format('M j, Y') : '—' }}</td>
                                <td class="text-right">{{ $daysHeld ?? '—' }}</td>
                                <td class="text-right">{{ $transaction->rate_applied !== null ? number_format($transaction->rate_applied, 2).'%' : '—' }}</td>
                                <td class="text-right">{{ $transaction->debit > 0 ? number_format($transaction->debit, 2) : '—' }}</td>
                                <td class="text-right">{{ $transaction->credit > 0 ? number_format($transaction->credit, 2) : '—' }}</td>
                                <td class="text-right">{{ number_format($transaction->cl_balance, 2) }}</td>
                                <td>{{ $transaction->description ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif

                @if ($investment->withdrawalRequests->isNotEmpty())
                    <h2>Withdrawal Requests</h2>
                    <table>
                        <tr>
                            <th>Requested</th>
                            <th class="text-right">Amount</th>
                            <th>Status</th>
                            <th>Paid</th>
                        </tr>
                        @foreach ($investment->withdrawalRequests as $request)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($request->notice_date)->format('M j, Y') }}</td>
                                <td class="text-right">${{ number_format($request->requested_amount, 2) }}</td>
                                <td>{{ $request->status->getLabel() }}</td>
                                <td>{{ $request->paid_at ? \Carbon\Carbon::parse($request->paid_at)->format('M j, Y') : '—' }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </div>
        @endforeach

        <div class="section">
            <h2>Aggregate Position</h2>
            <div class="valuation-box">
                <div class="row">
                    <div class="label">Total Principal Invested</div>
                    <div class="value">${{ number_format($totalPrincipal, 2) }}</div>
                </div>
                <div class="row">
                    <div class="label">Total Investment Value</div>
                    <div class="value">${{ number_format($totalValue, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="footer">This statement was generated on {{ now()->format('M d, Y') }} and reflects posted ledger
            activity through {{ $asOfDate->format('M d, Y') }}. It does not constitute a request for withdrawal or a
            modification of any Investment Agreement.</div>
    </div>
</body>

</html>
