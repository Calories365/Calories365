<?php

namespace App\Http\Controllers;

use App\Http\Requests\DateValidationRequest;
use App\Models\FoodConsumption;

class CalendarController extends Controller
{
    public function showRange(DateValidationRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $date = $request->route('date');
        $dailyCalories = FoodConsumption::getDailyCaloriesSum($user->id, $date);

        return response()->json([
            'dailyCalories' => $dailyCalories,
        ]);
    }
}
