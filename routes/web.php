<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ShrineController;
use App\Support\DirectoryRegistry;
use App\Support\ShrineRegistry;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/sitemap.xml', function () {
    $urls = [[
        'loc' => rtrim((string) config('app.url'), '/').'/',
        'changefreq' => 'weekly',
        'priority' => '1.0',
    ]];

    foreach (ShrineRegistry::slugs() as $slug) {
        $urls[] = [
            'loc' => ShrineRegistry::canonicalUrl($slug),
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ];
    }

    return response()
        ->view('sitemap', ['urls' => $urls])
        ->header('Content-Type', 'application/xml');
});

Route::post('/practitioner-presence', [ShrineController::class, 'heartbeat']);

foreach (ShrineRegistry::slugs() as $slug) {
    Route::prefix(ltrim(ShrineRegistry::apiBase($slug), '/'))->group(function () use ($slug): void {
        Route::get('/', [ShrineController::class, 'index'])->defaults('shrine', $slug);
        Route::get('/offerings/state', [ShrineController::class, 'state'])->defaults('shrine', $slug);
        Route::post('/butter-lamps', [ShrineController::class, 'store'])->defaults('shrine', $slug);
        Route::post('/mantra-repetitions', [ShrineController::class, 'storeMantra'])->defaults('shrine', $slug);
        Route::post('/incense-offerings', [ShrineController::class, 'storeIncense'])->defaults('shrine', $slug);
        Route::post('/flower-offerings', [ShrineController::class, 'storeFlower'])->defaults('shrine', $slug);
        Route::post('/music-offerings', [ShrineController::class, 'storeMusic'])->defaults('shrine', $slug);
        Route::post('/music-suggestions', [ShrineController::class, 'storeMusicSuggestion'])->defaults('shrine', $slug);
        Route::post('/water-offerings', [ShrineController::class, 'storeWater'])->defaults('shrine', $slug);
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->defaults('shrine', $slug);
    });
}
