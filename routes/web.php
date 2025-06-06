<?php

use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])
//    ->name('social.google.redirect');

Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])
    ->name('social.google.callback');

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::any('/{any?}', function () {
    return view('app');
})->where('any', '.*')->name('login');

Route::get('/reset-password/{token}', function ($token) {
    return view('auth.password-reset', ['token' => $token]);
})
    ->middleware(['guest:'.config('fortify.guard')])
    ->name('password.reset');
