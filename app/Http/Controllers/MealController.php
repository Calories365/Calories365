<?php

namespace App\Http\Controllers;

use App\Http\Requests\DateValidationRequest;
use App\Http\Requests\QuantityValidationRequest;
use App\Http\Requests\StoreFoodConsumptionRequest;
use App\Http\Resources\MealCollection;
use App\Models\FoodConsumption;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class MealController extends Controller
{
    /**
     * Create a meal entry for the current user.
     *
     * @throws AuthorizationException
     */
    public function store(StoreFoodConsumptionRequest $request): JsonResponse
    {
        $this->authorize('create', FoodConsumption::class);

        $validatedData = $request->validated();
        $validatedData['user_id'] = $request->user()->id;

        $foodConsumption = FoodConsumption::createFoodConsumption($validatedData);

        return response()->json(['id' => $foodConsumption->id]);
    }

    /**
     * Get a list of meals for a given date (for the current user).
     *
     * @throws AuthorizationException
     */
    public function index(DateValidationRequest $request): MealCollection
    {
        $this->authorize('viewAny', FoodConsumption::class);

        $date = $request->validated()['date'];
        $locale = app()->getLocale();
        $userId = $request->user()->id;

        $meals = FoodConsumption::getMealsWithCurrentDate($date, $userId, $locale);

        return new MealCollection($meals);
    }

    /**
     * Delete a meal entry.
     *
     * @throws AuthorizationException
     */
    public function destroy(FoodConsumption $meal): JsonResponse
    {
        $this->authorize('delete', $meal);

        $meal->delete();

        return response()->json(['message' => 'Success']);
    }

    /**
     * Update the quantity of a meal entry.
     *
     * @throws AuthorizationException
     */
    public function update(QuantityValidationRequest $request, FoodConsumption $meal): JsonResponse
    {
        $this->authorize('update', $meal);

        $meal->update(['quantity' => $request->validated()['quantity']]);

        return response()->json(['message' => 'Success']);
    }
}
