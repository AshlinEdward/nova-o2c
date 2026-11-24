
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automatically reject credit-held orders older than 48 hours.
Schedule::command('orders:reject-expired-credit-holds')
    ->everyFiveMinutes();

// Automatically retry pending webhook deliveries.
Schedule::command('webhooks:retry')
    ->everyMinute();
