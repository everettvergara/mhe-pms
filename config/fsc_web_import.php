<?php

return [
    'confirmation_phrase' => 'I CONFIRM AND UNDERSTAND',

    'mysql' => [
        'host' => env('FSC_WEB_DB_HOST'),
        'port' => env('FSC_WEB_DB_PORT', 3306),
        'database' => env('FSC_WEB_DB_DATABASE'),
        'username' => env('FSC_WEB_DB_USERNAME'),
        'password' => env('FSC_WEB_DB_PASSWORD', ''),
    ],
];
