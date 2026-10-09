<?php

return [
    'confirmation_phrase' => 'I CONFIRM AND UNDERSTAND',

    'operator_usernames' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('FSC_WEB_IMPORT_OPERATORS', 'admin')),
    ))),

    'allow_source_deactivate' => (bool) env('FSC_WEB_IMPORT_ALLOW_SOURCE_DEACTIVATE', false),

    'preview_ttl_minutes' => (int) env('FSC_WEB_IMPORT_PREVIEW_TTL_MINUTES', 30),

    'mhe_transaction_access_type' => 'MHE Transaction',

    'mysql' => [
        'host' => env('FSC_WEB_DB_HOST'),
        'port' => env('FSC_WEB_DB_PORT', 3306),
        'database' => env('FSC_WEB_DB_DATABASE'),
        'username' => env('FSC_WEB_DB_USERNAME'),
        'password' => env('FSC_WEB_DB_PASSWORD', ''),
    ],
];
