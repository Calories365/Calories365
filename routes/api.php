<?php

use App\Http\Controllers\VoiceController;
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

        Route::post('/user-meals', [\App\Http\Controllers\UsersMealController::class, 'store'])->name('user-meals.store');

        Route::get('/calendar/{date}', [\App\Http\Controllers\CalendarController::class, 'showRange'])->name('calendar.showRange');

        Route::get('/products/search', [\App\Http\Controllers\SearchProductController::class, 'search'])->name('meals.search');

        Route::get('/user', [\App\Http\Controllers\UserController::class, 'show'])->name('show');

        Route::get('/telegram-link', [\App\Http\Controllers\TelegramLinkController::class, 'getLink'])
            ->name('telegram.link');

        Route::post('/buy-premium', [\App\Http\Controllers\PaymentController::class, 'generatePaymentLink'])->name('buyPremium');

        // Маршрут для загрузки голосовых записей
        Route::post('/voice/upload', [VoiceController::class, 'upload'])->name('voice.upload');

        // Маршрут для сохранения продуктов
        Route::post('/voice/save-products', [VoiceController::class, 'saveProducts'])->name('voice.saveProducts');

        // Маршрут для генерации данных продукта
        Route::post('/voice/generate-product', [VoiceController::class, 'generateProductData'])->name('voice.generateProduct');

        // Маршрут для поиска продукта по названию
        Route::post('/voice/search-product', [VoiceController::class, 'searchProduct'])->name('voice.searchProduct');
    });
});
Route::middleware('check.bot.key')->group(function () {
    Route::group(['namespace' => 'App\Http\Controllers'], function () {
        Route::post('/caloriesEndPoint', [\App\Http\Controllers\CaloriesAPIBotController::class, 'store'])
            ->name('calculations.store2');

        Route::post('/caloriesEndPoint/saveProduct', [\App\Http\Controllers\CaloriesAPIBotController::class, 'saveProduct'])
            ->name('calculations.saveProduct');

        Route::post('/caloriesEndPoint/saveFoodConsumption', [\App\Http\Controllers\CaloriesAPIBotController::class, 'saveFoodConsumption'])
            ->name('calculations.saveFoodConsumption');

        Route::get('/caloriesEndPoint/showUserStats/{date}/{partOfDay?}', [\App\Http\Controllers\CaloriesAPIBotController::class, 'showUserStats'])
            ->name('calculations.showUserStats');

        Route::delete('/caloriesEndPoint/deleteMeal/{meal}', [\App\Http\Controllers\CaloriesAPIBotController::class, 'destroy'])
            ->name('calculations.destroy');

        Route::post('/caloriesEndPoint/checkTelegramCode', [\App\Http\Controllers\TelegramLinkController::class, 'checkTelegramCode'])
            ->name('caloriesEndPoint.checkTelegramCode');

        Route::get('/caloriesEndPoint/users-for-bot-multiple', [\App\Http\Controllers\UserController::class, 'showUsersInfoForBotMultiple']);

        Route::post('/caloriesEndPoint/toggleRussianLanguage', [\App\Http\Controllers\LanguageSettingController::class, 'toggleRussianLanguage'])
            ->name('language.toggleRussian');

        Route::get('/caloriesEndPoint/all-users', [\App\Http\Controllers\UserController::class, 'showAllUsers']);
    });
});

use App\Http\Controllers\SocialAuthController;

Route::get('/language/status', [\App\Http\Controllers\LanguageSettingController::class, 'getRussianLanguageStatus'])
    ->name('language.status');

Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])
    ->name('social.google.redirect');
