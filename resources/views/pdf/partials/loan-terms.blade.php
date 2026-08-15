{{-- Shared loan clauses (Payment Terms / Prepayment / Default), used by both the
     single-tranche loan agreement and each loan section of the combined agreement so
     both documents always state identical terms for a loan tranche.
     Expects: $investment, $payoutSchedule, $loanRate --}}
<div class="section">
    <h2>Payment Terms</h2>
    <p>
        Interest on the principal shall accrue at {{ number_format($loanRate, 2) }}% per annum, calculated
        on the outstanding principal balance, which shall remain unchanged throughout the term of this
        loan, and shall be paid in cash to the Lender {{ $investment->payout_frequency ? strtolower($investment->payout_frequency->getLabel()) : 'as scheduled below' }},
        on the following schedule:
    </p>
    <table>
        <tr>
            <th>Period</th>
            <th>Due Date</th>
            <th>Interest Amount (USD)</th>
        </tr>
        @foreach ($payoutSchedule as $row)
            <tr>
                <td>{{ $row['period_start']->format('M j, Y') }} – {{ $row['period_end']->format('M j, Y') }}</td>
                <td>{{ $row['due_date']->format('M j, Y') }}</td>
                <td>{{ number_format($row['amount'], 2) }}</td>
            </tr>
        @endforeach
    </table>
    <p>
        The entire outstanding principal balance of ${{ number_format($investment->principal_amount, 2) }}
        shall be due and payable in full on the Maturity Date,
        {{ $investment->maturity_date?->format('F j, Y') ?? '—' }}.
    </p>
</div>

<div class="section">
    <h2>Prepayment</h2>
    <p>{{ config('investment.legal.prepayment_clause') }}</p>
</div>

<div class="section">
    <h2>Default</h2>
    <p>{{ str_replace(':days', (string) config('investment.legal.default_notice_days'), config('investment.legal.default_clause')) }}</p>
</div>
