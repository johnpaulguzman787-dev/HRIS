<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:auto-clockout')->dailyAt('00:01');
Schedule::command('attendance:mark-holidays')->dailyAt('23:55');
Schedule::command('payroll:cutoff-notifications')->dailyAt('08:00');