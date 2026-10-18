<?php

use App\Http\Controllers\Api\LinkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/me', fn (Request $request) => ['data' => $request->user()->only('id', 'name', 'email')]);

    Route::get('/links', [LinkController::class, 'index']);
    Route::post('/links', [LinkController::class, 'store']);
    Route::get('/links/{code}', [LinkController::class, 'show']);
    Route::patch('/links/{code}', [LinkController::class, 'update']);
    Route::delete('/links/{code}', [LinkController::class, 'destroy']);
    Route::get('/links/{code}/stats', [LinkController::class, 'stats']);
});
