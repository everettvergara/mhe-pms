<?php

return [
    'attachments' => [
        'max_file_size_kb' => (int) env('PMS_ATTACHMENT_MAX_KB', 25600),
        'max_per_record' => (int) env('PMS_ATTACHMENT_MAX_PER_RECORD', 10),
        'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],
];
