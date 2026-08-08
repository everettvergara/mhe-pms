<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('mhe:generate-monthly-capacity')
    ->monthlyOn(1, '00:05')
    ->timezone(config('mhe.scheduler_timezone', 'Asia/Manila'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/mhe-monthly-capacity.log'));
