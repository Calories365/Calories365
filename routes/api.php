<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
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
//Route::get('/getPopularProducts', [\App\Http\Controllers\DairyController::class, 'getPopularProducts'])->name('getPopularProducts');

Route::middleware('auth:sanctum')->group(function () {
    Route::group(['namespace' => 'App\Http\Controllers'], function () {
        Route::get('/test', function () {
            return \App\Models\User::all();
        });
        Route::post('/save-calculation-data', [\App\Http\Controllers\CalculationController::class, 'store'])->name('save-calculation-data');
        Route::get('/get-calculation-data', [\App\Http\Controllers\CalculationController::class, 'get'])->name('get-calculation-data');
        Route::get('/getWithCache', [ApiController::class, 'getWithCache'])->name('getWithCache');
        Route::get('/all', [ApiController::class, 'all'])->name('all');

        Route::get('/getPopularProducts', [\App\Http\Controllers\DairyController::class, 'getPopularProducts'])->name('getPopularProducts');
        Route::post('/saveMeal', [\App\Http\Controllers\DairyController::class, 'saveMeal'])->name('saveMeal');
        Route::post('/getMeal', [\App\Http\Controllers\DairyController::class, 'getMeal'])->name('getMeal');
        Route::post('/updateMeal', [\App\Http\Controllers\DairyController::class, 'updateMeal'])->name('updateMeal');
        Route::delete('/deleteMeal/{id}', [\App\Http\Controllers\DairyController::class, 'deleteMeal'])->name('deleteMeal');
        Route::get('/getSearchedMeal', [\App\Http\Controllers\DairyController::class, 'getSearchedMeal'])->name('getSearchedMeal');

    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->only(['email', 'name', 'email_verified_at', 'calories_limit', 'id']);
});

//Route::group(['namespace' => 'App\Http\Controllers'], function () {
//    Route::get(' / get', 'ApiController@get')->name('get');
//    Route::get(' / getWithCache', 'ApiController@getWithCache')->name('getWithCache');
//    Route::get(' / all', 'ApiController@all')->name('all');
//});
