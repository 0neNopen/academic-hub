<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Jadwalkan pengecekan tugas setiap 15 menit dengan proteksi anti-overlap
Schedule::command('assignments:send-reminders')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

