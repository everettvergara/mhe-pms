<?php

return [
    'site_key' => env('RECAPTCHA_SITE_KEY'),
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    'score_threshold' => (float) env('RECAPTCHA_SCORE_THRESHOLD', 0.5),
    'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
];
