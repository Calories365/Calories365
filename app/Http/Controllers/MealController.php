<?php

namespace App\Http\Controllers;

use App\Http\Requests\DateValidationRequest;
use App\Http\Requests\QuantityValidationRequest;
use App\Http\Requests\StoreFoodConsumptionRequest;
use App\Http\Resources\MealCollection;
use App\Models\FoodConsumption;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

class MealController extends Controller
{
    public function store(StoreFoodConsumptionRequest $request): \Illuminate\Http\JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = auth()->id();
        $foodConsumption = FoodConsumption::createFoodConsumption($validatedData);
        return response()->json(['id' => $foodConsumption->id]);
    }

    public function index(DateValidationRequest $request): MealCollection
    {
        $date = $request->route('date');
        $locale = app()->getLocale();
        $userId = auth()->id();
        $meals = FoodConsumption::getMealsWithCurrentDate($date, $userId, $locale);
        return new MealCollection($meals);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(FoodConsumption $meal): \Illuminate\Http\JsonResponse
    {
        $this->authorize('delete', $meal);
        $meal->delete();
        return response()->json(['message' => 'Success']);
    }

    public function update(QuantityValidationRequest $request, FoodConsumption $meal): \Illuminate\Http\JsonResponse
    {
        $meal->update(['quantity' => $request->quantity]);
        return response()->json(['message' => 'Success']);
    }
}
