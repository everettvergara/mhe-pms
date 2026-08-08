<?php

return [
    'default_daily_hours' => (int) env('MHE_DEFAULT_DAILY_HOURS', 24),
    'uptime_target_pct' => (float) env('MHE_UPTIME_TARGET_PCT', 95),
    'scheduler_timezone' => env('MHE_SCHEDULER_TIMEZONE', 'Asia/Manila'),
];
