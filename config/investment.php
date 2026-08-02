<?php

return [
    // Minimum amount for a partial withdrawal request.
    'partial_minimum' => 5000,

    // Minimum balance that must remain after a partial withdrawal, unless staff grants an exception.
    'minimum_remaining_balance' => 10000,

    // Required written-notice period before the company must act on a withdrawal request.
    'notice_days' => 90,

    // Standard payment window after approval, before any staff-granted deferral.
    'payment_window_days' => 60,

    // Maximum additional deferral staff may grant for liquidity reasons.
    'max_deferral_days' => 180,

    // How long an investment may sit in pending_payment before it's flagged as
    // stuck (checkout started but no webhook success/failure was ever recorded).
    'stuck_pending_payment_hours' => 1,

    'legal' => [
        'governing_law' => 'This Agreement shall be governed by and construed in accordance with the laws applicable to KasaBazaar Group Of Companies in its jurisdiction of registration.',

        // Loan-type agreements only. Fixed company-wide wording — not a per-investment
        // field — so every loan uses identical, consistent terms.
        'prepayment_clause' => 'The Company may prepay all or any portion of the outstanding principal at any time prior to the Maturity Date without penalty or premium, provided that any accrued and unpaid interest through the date of prepayment is paid concurrently with such prepayment.',

        'default_clause' => 'If the Company fails to make any interest payment or the principal repayment when due under this Agreement, and such failure continues for :days days after the Lender provides written notice of the default to the Company, the Company shall be in default of this Agreement, and the entire outstanding principal balance, together with any accrued and unpaid interest, shall become immediately due and payable.',

        'default_notice_days' => 15,
    ],
];
