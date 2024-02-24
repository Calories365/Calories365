<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsersFoodConsumptionsRequest;
use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\ProductTranslation;
use DoubleMetaphone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use voku\helper\ASCII;

class UsersMealController extends Controller
{
    public function store(StoreUsersFoodConsumptionsRequest $request): \Illuminate\Http\JsonResponse
    {

        Log::info('Request data received in store method:', $request->all());

        // Сначала валидируем данные запроса
        $validatedData = $request->validated();

        // Используем транзакции, чтобы убедиться, что все изменения будут выполнены полностью
        DB::beginTransaction();
        try {
            // Создаем новый продукт
            $product = new Product([
                'user' => auth()->id(),
                'calories' => $validatedData['calories'],
                'proteins' => $validatedData['proteins'],
                'carbohydrates' => $validatedData['carbohydrates'],
                'fats' => $validatedData['fats'],
                'fibers' => $validatedData['fibers'],
            ]);

            $product->save();

            // Создаем перевод продукта
            $transiltedData = ASCII::to_transliterate($validatedData['name']);
            $doubleMetaphoneName = new DoubleMetaphone($transiltedData);
            $translation = new ProductTranslation([
                'product_id' => $product->id,
                'locale' => app()->getLocale(),
                'name' => $validatedData['name'],
                'transliterated_name' => $transiltedData,
                'double_metaphoned_name' => $doubleMetaphoneName->primary,
            ]);

            $translation->save();

            $consumption = new FoodConsumption([
                'user_id' => auth()->id(),
                'food_id' => $product->id,
                'quantity' => $validatedData['quantity'],
                'part_of_day' => $validatedData['part_of_day'],
                'consumed_at' => $validatedData['consumed_at'],
            ]);

            $consumption->save();

            // Если все в порядке, подтверждаем транзакцию
            DB::commit();

            // Возвращаем ответ, например, ID потребления продукта
            return response()->json([
                'consumption_id' => $consumption->id,
                'food_id' => $product->id, // Добавляем ID продукта в ответ
            ], 201);
        } catch (\Exception $e) {
            // В случае ошибки откатываем изменения
            DB::rollBack();

            // Возвращаем сообщение об ошибке
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
