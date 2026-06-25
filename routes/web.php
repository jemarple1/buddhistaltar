<?php

use App\Http\Controllers\ShrineController;
use App\Support\ShrineRegistry;
use Illuminate\Support\Facades\Route;

Route::post('/practitioner-presence', [ShrineController::class, 'heartbeat']);

foreach (ShrineRegistry::slugs() as $slug) {
    $prefix = ShrineRegistry::apiBase($slug);

    $register = function () use ($slug): void {
        Route::get('/', [ShrineController::class, 'index'])->defaults('shrine', $slug);
        Route::get('/offerings/state', [ShrineController::class, 'state'])->defaults('shrine', $slug);
        Route::post('/butter-lamps', [ShrineController::class, 'store'])->defaults('shrine', $slug);
        Route::post('/mantra-repetitions', [ShrineController::class, 'storeMantra'])->defaults('shrine', $slug);
        Route::post('/incense-offerings', [ShrineController::class, 'storeIncense'])->defaults('shrine', $slug);
        Route::post('/flower-offerings', [ShrineController::class, 'storeFlower'])->defaults('shrine', $slug);
        Route::post('/music-offerings', [ShrineController::class, 'storeMusic'])->defaults('shrine', $slug);
        Route::post('/music-suggestions', [ShrineController::class, 'storeMusicSuggestion'])->defaults('shrine', $slug);
        Route::post('/water-offerings', [ShrineController::class, 'storeWater'])->defaults('shrine', $slug);
    };

    if ($prefix === '') {
        $register();
    } else {
        Route::prefix(ltrim($prefix, '/'))->group($register);
    }
}
