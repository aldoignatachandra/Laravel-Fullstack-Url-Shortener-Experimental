<?php

use App\Models\Link;
use App\Models\LinkLog;
use App\Services\ShortCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

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

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('landing');

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('links', 'links.index')->name('links.index');

    Route::post('/links', function (Request $request) {
        $validated = $request->validate([
            'original_url' => 'required|url|max:2048',
            'title' => 'nullable|string|max:100',
        ]);

        Link::create([
            'user_id' => auth()->id(),
            'original_url' => $validated['original_url'],
            'title' => $validated['title'] ?: null,
            'short_code' => ShortCodeService::generateUnique(),
            'status' => 1,
        ]);

        return redirect('/links');
    })->name('links.store');

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
