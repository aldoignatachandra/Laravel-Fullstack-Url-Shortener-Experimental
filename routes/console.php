<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─────────────────────────────────────────────────────────────
// Production Garbage Collection Schedule
// ─────────────────────────────────────────────────────────────

// Telescope: prune debug data older than 7 days (daily at 2 AM)
Schedule::command('telescope:prune --hours=168')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->after(function () {
        Log::channel('scheduler')->info('Telescope pruned', ['command' => 'telescope:prune']);
    });

// Pulse: trim aggregated data (auto-trim built-in, but schedule explicit trim daily)
Schedule::command('pulse:trim')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->after(function () {
        Log::channel('scheduler')->info('Pulse trimmed', ['command' => 'pulse:trim']);
    });

// Queue Batches: prune finished batches older than 24 hours
Schedule::command('queue:prune-batches --hours=24')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->after(function () {
        Log::channel('scheduler')->info('Queue batches pruned', ['command' => 'queue:prune-batches']);
    });

// Failed Jobs: prune failed jobs older than 7 days
Schedule::command('queue:prune-failed --hours=168')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->after(function () {
        Log::channel('scheduler')->info('Failed jobs pruned', ['command' => 'queue:prune-failed']);
    });

// Password Reset OTPs: delete expired OTPs older than 1 hour
Schedule::call(function () {
    $deleted = DB::table('password_reset_otps')
        ->where('expires_at', '<', now()->subHour())
        ->delete();

    Log::channel('scheduler')->info('Expired OTPs cleaned', [
        'deleted' => $deleted,
    ]);
})->hourly()->name('cleanup-expired-otps')->withoutOverlapping();

// Password Reset Tokens: delete tokens older than 1 hour
Schedule::call(function () {
    $deleted = DB::table('password_reset_tokens')
        ->where('created_at', '<', now()->subHour())
        ->delete();

    Log::channel('scheduler')->info('Expired tokens cleaned', [
        'deleted' => $deleted,
    ]);
})->hourly()->name('cleanup-expired-tokens')->withoutOverlapping();

// Sessions: delete stale sessions older than 7 days
Schedule::call(function () {
    $deleted = DB::table('sessions')
        ->where('last_activity', '<', now()->subDays(7)->timestamp)
        ->delete();

    Log::channel('scheduler')->info('Stale sessions cleaned', [
        'deleted' => $deleted,
    ]);
})->dailyAt('04:00')->name('cleanup-stale-sessions')->withoutOverlapping();
