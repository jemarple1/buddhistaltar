<?php

use App\Http\Controllers\ShrineController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShrineController::class, 'index']);
Route::get('/offerings/state', [ShrineController::class, 'state']);
Route::post('/butter-lamps', [ShrineController::class, 'store']);
Route::post('/mantra-repetitions', [ShrineController::class, 'storeMantra']);
Route::post('/incense-offerings', [ShrineController::class, 'storeIncense']);
Route::post('/flower-offerings', [ShrineController::class, 'storeFlower']);
Route::post('/music-offerings', [ShrineController::class, 'storeMusic']);
Route::post('/music-suggestions', [ShrineController::class, 'storeMusicSuggestion']);
Route::post('/water-offerings', [ShrineController::class, 'storeWater']);
Route::post('/practitioner-presence', [ShrineController::class, 'heartbeat']);
