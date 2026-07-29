<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Investment Agreement - {{ $investment->reference }}</title>
</head>

<body>
    <div class="container">
        @include('pdf.partials.investment-pdf-header', [
            'docTitle' => 'Investment Agreement',
            'docSubtitle' => $investment->reference,
        ])

        @if ($investment->status->value === 'pending_payment')
            <div class="valuation-box" style="border-color: #b45309; background: #fffbeb;">
                <strong>Preview — Payment Not Yet Received.</strong>
                This document previews the terms of your proposed investment of
                ${{ number_format($investment->principal_amount, 2) }}. It is provided so you may review the
                agreement before funding. This Agreement does not take effect, and no obligations arise for either
                party, until the Company confirms receipt of the Investor's funds.
            </div>
        @endif

        <div class="section">
            <p>This Investment Agreement ("Agreement") is entered into between KasaBazaar Group Of Companies
                ("Company") and {{ $investor->name }} ("Investor").</p>
        </div>

        <div class="section">
            <h2>Nature of Investment</h2>
            <p>
                The parties expressly agree that the funds described in this Agreement constitute a private
                investment loan from the Investor to the Company. The Investor shall be entitled only to repayment of
                principal and the contractual returns described herein. This investment is intended to create a
                debtor-creditor relationship between the Company and the Investor and shall not be construed as the
                purchase of membership interests, shares, partnership interests, or any other form of equity ownership
                in the Company.
            </p>
        </div>

        <div class="section">
            <h2>Investment Contribution</h2>
            <table>
                <tr>
                    <th>Reference</th>
                    <th>Amount (USD)</th>
                    <th>{{ $investment->status->value === 'pending_payment' ? 'Proposed Start Date' : 'Date Received' }}</th>
                    <th>Term</th>
                    <th>Maturity Date</th>
                </tr>
                <tr>
                    <td>{{ $investment->reference }}</td>
                    <td>{{ number_format($investment->principal_amount, 2) }}</td>
                    <td>{{ \Carbon\Carbon::parse($investment->start_date)->format('F j, Y') }}</td>
                    <td>{{ $investment->contract_term_months }} months</td>
                    <td>{{ $investment->maturity_date?->format('F j, Y') ?? '—' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>Return on Investment</h2>
            <p>
                Interest shall be calculated on an annual-compounding basis, accruing daily using a 365-day year and
                prorated for partial-year periods. Accrued interest is credited and added to principal at the end of
                each calendar year; thereafter, interest for subsequent years is calculated on the increased balance.
                The following rates apply to this investment:
            </p>
            <table>
                <tr>
                    <th>Year</th>
                    <th>Annual Rate</th>
                    <th>Source</th>
                </tr>
                @foreach ($rateHistory as $year => $rate)
                    <tr>
                        <td>{{ $year }}</td>
                        <td>{{ $rate['rate'] !== null ? number_format($rate['rate'], 2).'%' : 'Not set' }}</td>
                        <td>{{ $rate['source'] === 'override' ? 'Negotiated rate' : ($rate['source'] === 'company_default' ? 'Company default' : ucfirst($rate['source'])) }}</td>
                    </tr>
                @endforeach
            </table>
            <p>
                Returns represent compensation payable by the Company on the investment loan and are not dependent
                upon the Company's profits, losses, revenues, or valuation.
            </p>
        </div>

        @if ($interestHistory->isNotEmpty())
            <div class="section">
                <h2>Interest Posted to Date</h2>
                <table>
                    <tr>
                        <th>Period</th>
                        <th>Rate</th>
                        <th>Days</th>
                        <th>Interest (USD)</th>
                    </tr>
                    @foreach ($interestHistory as $txn)
                        <tr>
                            <td>
                                @if ($txn->period_start && $txn->period_end)
                                    {{ $txn->period_start->format('M j, Y') }} – {{ $txn->period_end->format('M j, Y') }}
                                @else
                                    Calendar Year {{ $txn->year }}
                                @endif
                            </td>
                            <td>{{ $txn->rate_applied !== null ? number_format($txn->rate_applied, 2).'%' : '—' }}</td>
                            <td>{{ $txn->period_start && $txn->period_end ? $txn->period_start->diffInDays($txn->period_end) + 1 : '—' }}</td>
                            <td>{{ number_format($txn->credit, 2) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        <div class="section">
            <h2>Investment Valuation</h2>
            <p>The parties acknowledge the following investment position as of {{ $valuation['as_of']->format('F j, Y') }}:</p>
            <div class="valuation-box">
                <div class="row">
                    <div class="label">Original Principal</div>
                    <div class="value">${{ number_format($valuation['principal'], 2) }}</div>
                </div>
                <div class="row">
                    <div class="label">Total Interest Earned</div>
                    <div class="value">${{ number_format($valuation['interest_earned_total'], 2) }}</div>
                </div>
                <div class="row">
                    <div class="label">Total Investment Value</div>
                    <div class="value">${{ number_format($valuation['compounded_balance'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Reporting</h2>
            <p>
                The Company shall provide the Investor with an annual investment statement showing the principal
                balance, accrued returns, total investment value, and significant business updates.
            </p>
        </div>

        <div class="section">
            <h2>Withdrawal of Investment</h2>
            <p>
                The Investor may request a partial or full withdrawal of the investment by providing not less than
                {{ config('investment.notice_days') }} days' prior written notice to the Company, specifying the
                amount requested. Partial withdrawals shall be permitted in amounts of not less than
                ${{ number_format(config('investment.partial_minimum'), 2) }} per request, provided that the
                remaining investment balance after such withdrawal is not less than
                ${{ number_format(config('investment.minimum_remaining_balance'), 2) }}, unless otherwise approved by
                the Company in writing. Upon expiration of the notice period, the Company shall review the withdrawal
                request and, subject to available liquidity and operational requirements, process payment within
                {{ config('investment.payment_window_days') }} days. If the Company determines in good faith that
                immediate payment would materially impair its liquidity or ability to conduct normal business
                operations, the Company may defer payment for up to an additional
                {{ config('investment.max_deferral_days') }} days, upon written notice to the Investor explaining the
                reason for the deferral. Any unpaid balance of an approved withdrawal request shall remain credited to
                the Investor's account and continue to accrue returns in accordance with this Agreement until paid.
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
                @if (file_exists(public_path('images/shipping-signature.png')))
                    <img src="{{ URL::to('images/shipping-signature.png') }}" alt="Authorized Signature" style="max-height: 60px;">
                @endif
                <div class="signature-line">
                    Founder &amp; CVO<br>
                    KasaBazaar Group Of Companies<br>
                    Date: {{date('F j, Y')}}
                </div>
            </div>
        </div>
    </div>
</body>

</html>
