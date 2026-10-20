<?php

return [
    // Existing CORE account only; operators must fund it through established banking workflows.
    'funding_account_id' => env('LOAN_FUNDING_ACCOUNT_ID'),

    // Educational example only: configurable parameters, never commercial terms.
    'products' => [
        'academic' => [
            'name' => 'Academic loan example',
            'currency' => 'COP',
            'annual_rate' => '0.0500',
            'term_months' => 12,
        ],
    ],
];
