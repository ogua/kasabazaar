<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Investment Agreement - {{ $investor->name }}</title>
</head>

<body>
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
            <table>
                <tr>
                    <th>Reference</th>
                    <th>Amount (USD)</th>
                    <th>Date Received</th>
                    <th>Term</th>
                    <th>Maturity Date</th>
                    <th>Current Value (USD)</th>
                </tr>
                @foreach ($investments as $investment)
                    <tr>
                        <td>{{ $investment->reference }}</td>
                        <td>{{ number_format($investment->principal_amount, 2) }}</td>
                        <td>{{ \Carbon\Carbon::parse($investment->start_date)->format('F j, Y') }}</td>
                        <td>{{ $investment->contract_term_months }} months</td>
                        <td>{{ $investment->maturity_date?->format('F j, Y') ?? '—' }}</td>
                        <td>{{ number_format($valuations[$investment->id]['compounded_balance'], 2) }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

        <div class="section">
            <h2>Return on Investment</h2>
            <p>
                Interest on each tranche is calculated on an annual-compounding basis, accruing daily using a 365-day
                year and prorated for partial-year periods, at the rate applicable to that tranche for each calendar
                year. Returns represent compensation payable by the Company on the investment loan and are not
                dependent upon the Company's profits, losses, revenues, or valuation.
            </p>
        </div>

        <div class="section">
            <h2>Aggregate Investment Valuation</h2>
            <p>The parties acknowledge the following aggregate position as of {{ $asOfDate->format('F j, Y') }}:</p>
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
