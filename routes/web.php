<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RedirectController;
use App\Livewire\Account\Settings as AccountSettings;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Links\Index as LinksIndex;
use App\Livewire\Links\Show as LinksShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'marketing.landing')->name('home');
Route::view('/docs', 'marketing.docs')->name('docs');
Route::view('/legal/terms', 'legal.terms')->name('legal.terms');
Route::view('/legal/privacy', 'legal.privacy')->name('legal.privacy');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('app')->group(function () {
        Route::get('/', LinksIndex::class)->name('links.index');
        Route::get('/links/{link}', LinksShow::class)->name('links.show');
        Route::get('/account', AccountSettings::class)->name('account');
    });
});

Route::get('/qr/{code}.svg', [RedirectController::class, 'qr'])
    ->where('code', '[A-Za-z0-9_-]{3,32}')
    ->middleware('throttle:120,1')
    ->name('qr');

// Must stay last: everything else at the root is a short code.
Route::get('/{code}', [RedirectController::class, 'show'])->where('code', '[A-Za-z0-9_-]{3,32}')->name('link.go');
Route::post('/{code}', [RedirectController::class, 'unlock'])->where('code', '[A-Za-z0-9_-]{3,32}')->middleware('throttle:30,1')->name('link.unlock');
