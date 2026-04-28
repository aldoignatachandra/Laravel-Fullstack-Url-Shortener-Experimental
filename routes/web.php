<?php

use App\Models\Link;
use App\Models\LinkLog;
use Illuminate\Support\Facades\Route;

Route::get('/s/{short_code}', function (string $short_code) {
    $link = Link::where('short_code', $short_code)
        ->where('status', 1)
        ->firstOrFail();

    LinkLog::create([
        'link_id' => $link->id,
        'clicked_at' => now(),
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
        'referrer' => request()->header('referer'),
    ]);

    return redirect($link->original_url, 301);
})->name('redirect');

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
