<?php

use App\Http\Controllers\Api\LinkController;
use App\Http\Controllers\AuthHub\SiteRegistryController;
use App\Http\Middleware\AuthHubAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Sign-in hub site registry, only on the hub host.
Route::domain(config('authhub.domain'))->prefix('hub')->middleware([AuthHubAdmin::class, 'throttle:60,1'])->group(function () {
    Route::get('/sites', [SiteRegistryController::class, 'index']);
    Route::post('/sites', [SiteRegistryController::class, 'store']);
    Route::get('/sites/{slug}', [SiteRegistryController::class, 'show']);
    Route::patch('/sites/{slug}', [SiteRegistryController::class, 'update']);
    Route::post('/sites/{slug}/rotate', [SiteRegistryController::class, 'rotate']);
    Route::delete('/sites/{slug}', [SiteRegistryController::class, 'destroy']);
});

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/me', fn (Request $request) => ['data' => $request->user()->only('id', 'name', 'email')]);

    Route::get('/links', [LinkController::class, 'index']);
    Route::post('/links', [LinkController::class, 'store']);
    Route::get('/links/{code}', [LinkController::class, 'show']);
    Route::patch('/links/{code}', [LinkController::class, 'update']);
    Route::delete('/links/{code}', [LinkController::class, 'destroy']);
    Route::get('/links/{code}/stats', [LinkController::class, 'stats']);
});
