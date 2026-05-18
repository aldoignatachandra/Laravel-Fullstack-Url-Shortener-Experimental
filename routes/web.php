<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Requests\StoreLinkRequest;
use App\Models\Link;
use App\Models\LinkLog;
use App\Services\ShortCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Google OAuth routes
Route::get('/auth/google', [GoogleController::class, 'redirect'])
    ->name('auth.google')
    ->middleware('throttle:5,1');

Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
    ->name('auth.google.callback');

Route::get('/s/{short_code}', function (string $short_code, Request $request) {
    $link = Link::where('short_code', $short_code)
        ->where('status', Link::STATUS_ACTIVE)
        ->firstOrFail();

    // Validate URL scheme to prevent open redirect attacks
    $parsedUrl = parse_url($link->original_url);
    if (! in_array($parsedUrl['scheme'] ?? '', ['http', 'https'], true)) {
        abort(404);
    }

    LinkLog::create([
        'link_id' => $link->id,
        'clicked_at' => now(),
        'ip_address' => $request->ip(),
        'user_agent' => mb_substr($request->userAgent() ?? '', 0, 500),
        'referrer' => filter_var($request->header('referer'), FILTER_VALIDATE_URL) ?: null,
    ]);

    return redirect($link->original_url, 301);
})->middleware('smart.throttle:api')->name('redirect');

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('landing');

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('links', 'links.index')->name('links.index');

    Route::post('/links', function (StoreLinkRequest $request) {
        $validated = $request->validated();

        Link::create([
            'user_id' => Auth::id(),
            'original_url' => $validated['original_url'],
            'title' => $validated['title'] ?: null,
            'short_code' => ShortCodeService::generateUnique(),
            'status' => Link::STATUS_ACTIVE,
        ]);

        return redirect('/links');
    })->middleware('smart.throttle:strict-api')->name('links.store');

    Route::delete('/links/{link}', function (Link $link) {
        Gate::authorize('delete', $link);

        $link->delete();

        return redirect('/links');
    })->name('links.destroy');

    Volt::route('links/{link}', 'links.show')->name('links.show');

    Volt::route('dashboard', 'dashboard')->name('dashboard');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
