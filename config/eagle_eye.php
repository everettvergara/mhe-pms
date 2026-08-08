<?php

return [
    'defaults' => [
        'site_code' => 'EE-UNMAPPED',
        'mhe_type_code' => 'EE-UNK',
        'mhe_category_code' => 'EE-UNKNOWN',
        'import_user_email' => 'import@system.local',
        'unknown_unit' => 'UNKNOWN',
    ],

    'seed_path' => database_path('data/eagle_eye'),

    'status_map' => [
        1 => 'Draft',
        2 => 'Posted',
        3 => 'Cancelled',
    ],

    'action_plan_status_map' => [
        1 => 'Pending',
        2 => 'Confirmed',
    ],
];
