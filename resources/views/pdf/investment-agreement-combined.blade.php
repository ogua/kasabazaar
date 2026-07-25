<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Investment Agreement - {{ $investor->name }}</title>
</head>

<body>
    @php
        $ordinalWords = ['First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth', 'Ninth', 'Tenth'];
        $ordinal = fn (int $n) => $ordinalWords[$n - 1] ?? "{$n}th";
        $clauseLabel = fn (int $i) => $i < 26 ? chr(97 + $i) : ($i + 1);
    @endphp

    <div class="container">
        @include('pdf.partials.investment-pdf-header', [
            'docTitle' => 'Investment Agreement',
            'docSubtitle' => $investor->name,
        ])

        <div class="section">
            <p>This Investment Agreement ("Agreement") is entered into between KasaBazaar LLC d/b/a Rose Door to Door
                Shipping &amp; Delivery Services ("Company") and {{ $investor->name }} ("Investor"), covering all
                investment tranches held by the Investor as of the date below.</p>
        </div>

        <div class="section">
            <h2>Nature of Investment</h2>
            <p>
                The parties expressly agree that the funds described in this Agreement constitute private investment
                loans from the Investor to the Company. The Investor shall be entitled only to repayment of principal
                and the contractual returns described herein. These investments are intended to create a
                debtor-creditor relationship between the Company and the Investor and shall not be construed as the
                purchase of membership interests, shares, partnership interests, or any other form of equity
                ownership in the Company.
            </p>
        </div>

        <div class="section">
            <h2>Investment Contributions</h2>
            @foreach ($investments as $i => $investment)
                <div class="valuation-box">
                    <div class="row">
                        <div class="label"><strong>Investment {{ $i + 1 }}</strong></div>
                        <div class="value"></div>
                    </div>
                    <div class="row">
                        <div class="label">Amount</div>
                        <div class="value">${{ number_format($investment->principal_amount, 2) }}</div>
                    </div>
                    <div class="row">
                        <div class="label">Date Received</div>
                        <div class="value">{{ \Carbon\Carbon::parse($investment->start_date)->format('F j, Y') }}</div>
                    </div>
                </div>
            @endforeach
            <div class="valuation-box">
                <div class="row">
                    <div class="label"><strong>Total Principal Invested</strong></div>
                    <div class="value"><strong>${{ number_format($totalPrincipal, 2) }}</strong></div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Return on Investment</h2>
            <p>The parties agree that:</p>
            @foreach ($rateClauses as $i => $clause)
                <p style="margin-bottom: 6px;"><strong>{{ $clauseLabel($i) }}.</strong> {{ $clause }}</p>
            @endforeach
        </div>

        <div class="section">
            <h2>Investment Valuation</h2>
            <p>The parties acknowledge the following investment balances, calculated in accordance with the annual
                compounding methodology described above:</p>

            @foreach ($investments as $i => $investment)
                @php
                    $valuation = $valuations[$investment->id];
                    $segments = $valuation['segments'];
                    $segmentCount = count($segments);
                @endphp
                <p style="font-weight: bold; color: #003151; text-transform: uppercase; margin: 12px 0 6px;">
                    {{ $ordinal($i + 1) }} Investment
                </p>
                <div class="valuation-box">
                    <div class="row">
                        <div class="label">Original Principal</div>
                        <div class="value">${{ number_format($valuation['principal'], 2) }}</div>
                    </div>
                    @foreach ($segments as $j => $segment)
                        @php
                            $isFullYear = $segment['period_start']->isSameDay(\Carbon\Carbon::create($segment['year'], 1, 1))
                                && $segment['period_end']->isSameDay(\Carbon\Carbon::create($segment['year'], 12, 31));
                        @endphp
                        <div class="row">
                            <div class="label">
                                @if ($isFullYear)
                                    Interest Earned During {{ $segment['year'] }} at {{ number_format($segment['rate'], 2) }}%
                                @else
                                    Interest Earned ({{ $segment['period_start']->format('F j, Y') }}–{{ $segment['period_end']->format('F j, Y') }}) at {{ number_format($segment['rate'], 2) }}% per annum, prorated for {{ $segment['days_held'] }} days
                                @endif
                            </div>
                            <div class="value">${{ number_format($segment['interest'], 2) }}</div>
                        </div>
                        @if ($j < $segmentCount - 1)
                            <div class="row">
                                <div class="label">Balance as of {{ $segment['period_end']->copy()->addDay()->format('F j, Y') }}</div>
                                <div class="value">${{ number_format($segment['balance_end'], 2) }}</div>
                            </div>
                        @endif
                    @endforeach
                    <div class="row" style="border-top: 1px solid #A0043C; padding-top: 6px; margin-top: 4px;">
                        <div class="label"><strong>Total Value of {{ $ordinal($i + 1) }} Investment as of {{ $valuation['as_of']->format('F j, Y') }}</strong></div>
                        <div class="value"><strong>${{ number_format($valuation['compounded_balance'], 2) }}</strong></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="section">
            <h2>Total Investment Position</h2>
            <p>The parties acknowledge the following aggregate position as of {{ $asOfDate->format('F j, Y') }}:</p>
            <div class="valuation-box">
                <div class="row">
                    <div class="label">Total Principal Invested</div>
                    <div class="value">${{ number_format($totalPrincipal, 2) }}</div>
                </div>
                <div class="row">
                    <div class="label">Total Accrued Interest</div>
                    <div class="value">${{ number_format($totalInterest, 2) }}</div>
                </div>
                <div class="row">
                    <div class="label">Total Investment Value</div>
                    <div class="value">${{ number_format($totalValue, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Reporting</h2>
            <p>
                The Company shall provide the Investor with an annual investment statement showing the principal
                balance, accrued returns, total investment value, and significant business updates for each tranche.
            </p>
        </div>

        <div class="section">
            <h2>Withdrawal of Investment</h2>
            <p>
                The Investor may request a partial or full withdrawal of any tranche by providing not less than
                {{ config('investment.notice_days') }} days' prior written notice to the Company. Partial withdrawals
                shall be permitted in amounts of not less than
                ${{ number_format(config('investment.partial_minimum'), 2) }} per request, provided that the
                remaining balance of that tranche after such withdrawal is not less than
                ${{ number_format(config('investment.minimum_remaining_balance'), 2) }}, unless otherwise approved by
                the Company in writing. Upon expiration of the notice period, the Company shall review the withdrawal
                request and, subject to available liquidity and operational requirements, process payment within
                {{ config('investment.payment_window_days') }} days, with the Company reserving the right to defer
                payment by up to an additional {{ config('investment.max_deferral_days') }} days for documented
                liquidity reasons. Any unpaid balance of an approved withdrawal request shall remain credited to the
                Investor's account and continue to accrue returns until paid.
            </p>
        </div>

        @include('pdf.partials.investment-legal-boilerplate')

        <div class="signature-block">
            <div class="signature-column">
                <div class="signature-line">
                    {{ $investor->name }}<br>
                    Investor<br>
                    Date: {{date('F j, Y')}}
                </div>
            </div>
            <div class="signature-column">
                <div class="signature-line">
                    Founder &amp; CVO<br>
                    KasaBazaar LLC d/b/a Rose Door to Door Shipping &amp; Delivery Services<br>
                    Date: {{date('F j, Y')}}
                </div>
            </div>
        </div>
    </div>
</body>

</html>
