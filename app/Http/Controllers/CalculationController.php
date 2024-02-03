<?php

namespace App\Http\Controllers;

use App\Http\Resources\CalculationResource;
use App\Models\User;
use App\Models\UserResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class CalculationController extends Controller
{
//    public function store(Request $request): \Illuminate\Http\JsonResponse
//    {
//        // Логирование входных данных
//        Log::info('Request data received in store method:', $request->all());
//
//        // Валидация данных
//        $validatedData = $request->validate([
//            'gender' => 'required|string',
//            'birthYear' => 'required',
//            'weight' => 'required|numeric',
//            'height' => 'required|numeric',
//            'goalWeight' => 'required|numeric',
//            'fat' => 'required|numeric',
//            'activity' => 'required|integer',
//            'goal' => 'required|integer',
//            'dailyCalories' => 'required|integer',
//            'checkboxActive' => 'required',
//        ]);
//
//        // Добавление user_id текущего пользователя
//        $userId = auth()->id();
//        $validatedData['user_id'] = $userId;
//
//        // Поиск и обновление существующей записи или создание новой
//        $userResult = UserResult::updateOrCreate(
//            ['user_id' => $userId], // Критерии поиска
//            $validatedData          // Данные для обновления/создания
//        );
//
//        Cache::forget("user_results_{$userId}");
//
//        // Возвращение данных сохраненной или обновленной записи
//        return response()->json($userResult, 201);
//    }
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        // Логирование входных данных
        Log::info('Request data received in store method:', $request->all());

        // Валидация данных
        $validatedData = $request->validate([
            'gender' => 'required|string',
            'birthYear' => 'required',
            'weight' => 'required|numeric',
            'height' => 'required|numeric',
            'goalWeight' => 'required|numeric',
            'fat' => 'required|numeric',
            'activity' => 'required|integer',
            'goal' => 'required|integer',
            'dailyCalories' => 'required|integer',
            'checkboxActive' => 'required',
        ]);

        // Получение ID текущего пользователя
        $userId = auth()->id();

        // Обновление поля calories_limit в таблице users
        User::where('id', $userId)->update(['calories_limit' => $validatedData['dailyCalories']]);

        // Удаление поля dailyCalories из массива, так как оно уже сохранено в таблице users
        unset($validatedData['dailyCalories']);

        // Добавление user_id текущего пользователя в массив для сохранения
        $validatedData['user_id'] = $userId;

        // Поиск и обновление существующей записи или создание новой в таблице user_results
        $userResult = UserResult::updateOrCreate(
            ['user_id' => $userId], // Критерии поиска
            $validatedData          // Данные для обновления/создания
        );

        // Очистка кэша для user_results
        Cache::forget("user_results_{$userId}");

        // Возвращение данных сохраненной или обновленной записи
        return response()->json($userResult, 201);
    }


    public function get()
    {
        $userId = auth()->id();

//         Кэширование запроса на получение данных текущего пользователя
        $userResult = Cache::remember("user_results_{$userId}", 60 * 60, function () use ($userId) {
            return UserResult::where('user_id', $userId)->first();
        });

        // Проверка, существует ли запись
        if (!$userResult) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        // Возвращение данных с использованием CalculationResource
        return new CalculationResource($userResult);
    }
}
