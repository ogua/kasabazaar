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

    /*
     * Letterhead for the bank-facing statements. These are the details a lender or
     * auditor reads off the top of the page, so they live here rather than in a blade.
     * The logo is resolved from public/ and simply omitted if the file is missing.
     */
    'company' => [
        'name' => env('FINANCIALS_COMPANY_NAME', 'Rose Door to Door & Delivery Company Limited'),
        'registration_number' => env('FINANCIALS_COMPANY_REG_NO'),
        'logo' => env('FINANCIALS_COMPANY_LOGO', 'images/logo.png'),
        'address' => env('FINANCIALS_COMPANY_ADDRESS', 'Adako Jachie, Ejisu, Kumasi, Ghana'),
        'phone_ghana' => env('FINANCIALS_COMPANY_PHONE_GH', '+233 50 972 5081 / +233 50 972 5073'),
        'phone_usa' => env('FINANCIALS_COMPANY_PHONE_US', '+1 (773) 970-0129 / +1 (574) 440-7460'),
        'email' => env('FINANCIALS_COMPANY_EMAIL', 'kasabazaar109@gmail.com'),
        'website' => env('FINANCIALS_COMPANY_WEBSITE', 'rddshipping.com'),
    ],
];
