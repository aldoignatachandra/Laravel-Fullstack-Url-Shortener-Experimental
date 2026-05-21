<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Requests\StoreLinkRequest;
use App\Models\Link;
use App\Models\LinkLog;
use App\Services\ShortCodeService;
use Illuminate\Database\QueryException;
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

    // Validate URL to prevent SSRF and open redirect attacks
    $parsedUrl = parse_url($link->original_url);
    $scheme = $parsedUrl['scheme'] ?? '';
    $host = $parsedUrl['host'] ?? '';

    // 1. Only allow http/https
    if (! in_array($scheme, ['http', 'https'], true)) {
        abort(404);
    }

    // 2. Block localhost variants
    $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1', '[::1]'];
    if (in_array($host, $blockedHosts, true)) {
        abort(404);
    }

    // 3. Block private/reserved IP ranges (AWS metadata, internal networks)
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        if (! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            abort(404);
        }
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

        $maxAttempts = ShortCodeService::MAX_GENERATION_ATTEMPTS;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                Link::create([
                    'user_id' => Auth::id(),
                    'original_url' => $validated['original_url'],
                    'title' => $validated['title'] ?: null,
                    'short_code' => ShortCodeService::generateUnique(),
                    'status' => Link::STATUS_ACTIVE,
                ]);

                return redirect('/links');
            } catch (QueryException $e) {
                // Duplicate short_code — retry with a new code
                if ($attempt === $maxAttempts - 1) {
                    throw $e;
                }
            }
        }
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

require __DIR__ . '/auth.php';
