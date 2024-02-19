<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::group(['namespace' => 'App\Http\Controllers'], function () {

        Route::post('/calculations', [\App\Http\Controllers\CalculationController::class, 'store'])->name('calculations.store');
        Route::get('/calculations', [\App\Http\Controllers\CalculationController::class, 'index'])->name('calculations.index');

        Route::get('/products/popular', [\App\Http\Controllers\PopularProductController::class, 'index'])->name('products.popular');

        Route::post('/meals', [\App\Http\Controllers\MealController::class, 'store'])->name('meals.store');
        Route::get('/meals/{date}', [\App\Http\Controllers\MealController::class, 'index'])->name('meals.index');
        Route::put('/meals/{meal}', [\App\Http\Controllers\MealController::class, 'update'])->name('meals.update');
        Route::delete('/meals/{meal}', [\App\Http\Controllers\MealController::class, 'destroy'])->name('meals.destroy');

        Route::get('/products/search', [\App\Http\Controllers\SearchProductController::class, 'search'])->name('meals.search');
        Route::get('/user', [\App\Http\Controllers\UserController::class, 'show'])->name('show');
    });
});


