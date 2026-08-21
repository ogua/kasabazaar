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
                Group Of Companies, as of {{ $asOfDate->format('F j, Y') }}.</p>
        </div>

        @foreach ($investments as $investment)
            @php
                $isLoan = $investment->capital_type === \App\Enums\InvestmentCapitalType::loan;
                $valuation = $valuations[$investment->id] ?? null;
            @endphp
            <div class="section">
                <h2>{{ $isLoan ? 'Loan' : 'Investment' }} {{ $investment->reference }}</h2>

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

                @if ($isLoan)
                    <div class="valuation-box">
                        <div class="row">
                            <div class="label">Maturity Date</div>
                            <div class="value">{{ $investment->maturity_date?->format('F j, Y') ?? '—' }}</div>
                        </div>
                        <div class="row">
                            <div class="label">Total Interest Accrued</div>
                            <div class="value">${{ number_format($investment->interestPayouts->sum('amount'), 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="label">Total Interest Paid</div>
                            <div class="value">${{ number_format($investment->interestPayouts->sum('amount_paid'), 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="label">Principal (due in full at maturity)</div>
                            <div class="value">${{ number_format($investment->principal_amount, 2) }}</div>
                        </div>
                    </div>
                @elseif ($valuation)
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
                @endif

                @if ($investment->isConverted())
                    @php
                        $conversionSource = $investment->conversionSources->last();
                        $conversion = $conversionSource?->conversion;
                    @endphp
                    {{-- A settled tranche has no live valuation — its capital now sits on the
                         successor. It stays on the statement so the closing balance below does
                         not simply drop with nothing to explain it. --}}
                    <div class="valuation-box" style="border-color: #1d4ed8; background: #eff6ff;">
                        <div class="row">
                            <div class="label">Converted On</div>
                            <div class="value">{{ $conversion?->conversion_date?->format('F j, Y') ?? '—' }}</div>
                        </div>
                        <div class="row">
                            <div class="label">Amount Carried Forward</div>
                            <div class="value">${{ number_format($conversionSource?->amount_rolled ?? 0, 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="label">Carried Forward To</div>
                            <div class="value">{{ $conversion?->targetInvestment?->reference ?? '—' }}</div>
                        </div>
                        <div class="row">
                            <div class="label">Closing Balance</div>
                            <div class="value">$0.00</div>
                        </div>
                    </div>
                @endif

                @if ($isLoan)
                    <h2>Interest Payout History</h2>
                    @if ($investment->interestPayouts->isEmpty())
                        <p>No interest accrued yet.</p>
                    @else
                        <table class="transactions-table">
                            <tr>
                                <th>Period</th>
                                <th>Due Date</th>
                                <th class="text-right">Rate</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">Paid</th>
                                <th>Status</th>
                            </tr>
                            @foreach ($investment->interestPayouts as $payout)
                                <tr>
                                    <td>{{ $payout->period_start->format('M j, Y') }} – {{ $payout->period_end->format('M j, Y') }}</td>
                                    <td>{{ $payout->due_date->format('M j, Y') }}</td>
                                    <td class="text-right">{{ number_format($payout->rate_applied, 2) }}%</td>
                                    <td class="text-right">{{ number_format($payout->amount, 2) }}</td>
                                    <td class="text-right">{{ number_format($payout->amount_paid, 2) }}</td>
                                    <td>{{ $payout->status->getLabel() }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                @endif

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
                @if ($totalLoanInterestAccrued > 0)
                    <div class="row">
                        <div class="label">Total Loan Interest Accrued</div>
                        <div class="value">${{ number_format($totalLoanInterestAccrued, 2) }}</div>
                    </div>
                    <div class="row">
                        <div class="label">Total Loan Interest Paid</div>
                        <div class="value">${{ number_format($totalLoanInterestPaid, 2) }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="signature-block">
            <div class="signature-column">
                @if (file_exists(public_path('images/shipping-signature.png')))
                    <img src="{{ URL::to('images/shipping-signature.png') }}" alt="Authorized Signature" style="max-height: 60px;">
                @endif
                <div class="signature-line">
                    Founder &amp; CVO<br>
                    KasaBazaar Group Of Companies<br>
                    Date: {{ now()->format('F j, Y') }}
                </div>
            </div>
        </div>

        <div class="footer">This statement was generated on {{ now()->format('M d, Y') }} and reflects posted ledger
            activity through {{ $asOfDate->format('M d, Y') }}. It does not constitute a request for withdrawal or a
            modification of any Investment Agreement.</div>
    </div>
</body>

</html>
