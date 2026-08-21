<?php

return [
    /*
     * The earliest year this system holds primary accounting records for. Years from
     * here on are derived from live cashbook, shipment, expense, payroll and investment
     * data; anything earlier has to be keyed in from the accountant's books via the
     * Financial Statement Entry screen, because the records simply are not here.
     */
    'first_recorded_year' => (int) env('FINANCIALS_FIRST_RECORDED_YEAR', 2026),

    /*
     * Presentation currency for the bank-facing statements. The cashbook is kept in
     * GHS and is translated at the fiscal period's closing rate; records that snapshot
     * their own rate (incomes, expenses, shipments, investments) are used as-is.
     */
    'presentation_currency' => 'USD',

    /*
     * Which records trading revenue and costs are read from.
     *
     *   'records'  — shipments, expenses and external income (ACCRUAL: revenue when a
     *                shipment is raised, costs when incurred). The unpaid remainder is
     *                what accounts receivable carries, so the two statements articulate.
     *   'cashbook' — the cashbook's monthly income/expenditure ledgers (CASH: revenue
     *                when received, costs when paid).
     *
     * Only ever one of them. The cashbook records the cash side of the very same
     * shipments and expenses, so reading both would count every transaction twice.
     */
    'trading_source' => env('FINANCIALS_TRADING_SOURCE', 'records'),

    'company' => [
        'name' => env('FINANCIALS_COMPANY_NAME', 'Rose Door to Door & Delivery Company Limited'),
        'registration_number' => env('FINANCIALS_COMPANY_REG_NO'),
    ],
];
