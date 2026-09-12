<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('saas:check-subscriptions')->daily();
Schedule::command('installment:send-reminders')->dailyAt('09:00');
Schedule::command('system:backup-database --clean-days=30')->dailyAt('02:00');
