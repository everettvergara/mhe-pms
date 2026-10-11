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

    'downtime_max_rows_per_run' => (int) env('FSC_WEB_DOWNTIME_MAX_ROWS', 2000),

    'downtime_preview_per_page' => 50,

    // fsc_web tb_sys_mf_status.id => mhe-pms DowntimeStatus. 4 is "For Approval".
    'downtime_status_map' => [
        1 => 'Draft',
        2 => 'Posted',
        3 => 'Cancelled',
        4 => 'Posted',
    ],

    'mysql' => [
        'host' => env('FSC_WEB_DB_HOST', '127.0.0.1'),
        'port' => env('FSC_WEB_DB_PORT', 3306),
        'database' => env('FSC_WEB_DB_DATABASE', 'eagleeyefastlogi_fsc_dashboard'),
        'username' => env('FSC_WEB_DB_USERNAME', 'eagleeyefastlogi_user'),
        'password' => env('FSC_WEB_DB_PASSWORD', ''),
    ],
];
