<?php

return [
    'invoice' => [
        'number_format' => env('BILLING_INVOICE_NUMBER_FORMAT', '{prefix}-{year}-{sequence}'),
        'number_prefix' => env('BILLING_INVOICE_NUMBER_PREFIX', 'INV'),
        'sequence_padding' => env('BILLING_INVOICE_SEQUENCE_PADDING', 6),
        'download_link_lifetime_minutes' => env('BILLING_INVOICE_DOWNLOAD_LIFETIME_MINUTES', 10080),
    ],

    'seller' => [
        'name' => env('BILLING_SELLER_NAME'),
        'address' => env('BILLING_SELLER_ADDRESS'),
        'email' => env('BILLING_SELLER_EMAIL'),
        'tax_identifier' => env('BILLING_SELLER_TAX_IDENTIFIER'),
        'registration_number' => env('BILLING_SELLER_REGISTRATION_NUMBER'),
        'country_code' => env('BILLING_SELLER_COUNTRY_CODE'),
    ],
];
