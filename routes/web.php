<?php

use App\Http\Controllers\ShrineController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShrineController::class, 'index']);
Route::get('/offerings/state', [ShrineController::class, 'state']);
Route::post('/butter-lamps', [ShrineController::class, 'store']);
Route::post('/mantra-repetitions', [ShrineController::class, 'storeMantra']);
Route::post('/incense-offerings', [ShrineController::class, 'storeIncense']);
Route::post('/flower-offerings', [ShrineController::class, 'storeFlower']);
Route::post('/water-bowls/acquire', [ShrineController::class, 'acquireWaterLock']);
Route::post('/water-bowls/fill', [ShrineController::class, 'fillWaterBowl']);
Route::post('/practitioner-presence', [ShrineController::class, 'heartbeat']);
