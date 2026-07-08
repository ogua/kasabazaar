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
        'governing_law' => 'This Agreement shall be governed by and construed in accordance with the laws applicable to KasaBazaar LLC d/b/a Rose Door to Door Shipping & Delivery Services in its jurisdiction of registration.',
    ],
];
