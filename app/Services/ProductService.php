<?php

namespace App\Services;

use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Traits\DoubleMetaphoneTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{
    use DoubleMetaphoneTrait;

    /**
     * @throws \Exception
     */
    public function createProductWithTranslationsAndConsumption($validatedData): array
    {
        DB::beginTransaction();
        try {
            $locale = app()->getLocale();
            $user_id = auth()->id();

            // Проверим наличие всех необходимых данных и установим дефолтные значения
            if (! isset($validatedData['name']) && isset($validatedData['product_translation']['name'])) {
                $validatedData['name'] = $validatedData['product_translation']['name'];
            }

            // Поиск активного продукта пользователя с таким же именем
            $productUserActive = ProductTranslation::query()
                ->where('locale', $locale)
                ->where('active', 1)
                ->where('user_id', $user_id)
                ->where('name', $validatedData['name'])
                ->first();

            if ($productUserActive) {
                ProductTranslation::query()
                    ->where('product_id', $productUserActive->product_id)
                    ->update(['active' => 0]);
            }

            $productVerifiedCheck = ProductTranslation::query()
                ->where('locale', $locale)
                ->where('name', $validatedData['name'])
                ->where(function ($query) {
                    $query->where('verified', 1)
                        ->orWhereNull('user_id');
                })
                ->first();

            if ($productVerifiedCheck) {
                $validatedData['verified'] = 0;
            }

            $validatedData['active'] = 1;

            // Подготовка данных для создания продукта, перенос полей из вложенного массива product
            $productData = [
                'user_id' => $validatedData['user_id'] ?? auth()->id(),
            ];

            // Если есть подмассив product, добавляем его поля в основной массив данных
            if (isset($validatedData['product']) && is_array($validatedData['product'])) {
                Log::info('Preparing product data from nested array', [
                    'product_data' => $validatedData['product'],
                ]);

                // Добавляем все поля из подмассива product
                $productData = array_merge($productData, $validatedData['product']);
            }

            // Перенос ключевых полей из основного массива, если в подмассиве их нет
            // Это нужно для обратной совместимости с существующим кодом
            if (! isset($productData['calories']) && isset($validatedData['calories'])) {
                $productData['calories'] = $validatedData['calories'];
            }
            if (! isset($productData['proteins']) && isset($validatedData['proteins'])) {
                $productData['proteins'] = $validatedData['proteins'];
            }
            if (! isset($productData['carbohydrates']) && isset($validatedData['carbohydrates'])) {
                $productData['carbohydrates'] = $validatedData['carbohydrates'];
            }
            if (! isset($productData['fats']) && isset($validatedData['fats'])) {
                $productData['fats'] = $validatedData['fats'];
            }
            if (! isset($productData['fibers']) && isset($validatedData['fibers'])) {
                $productData['fibers'] = $validatedData['fibers'];
            }

            // Логируем данные для создания продукта
            Log::info('Final product data for creation', $productData);

            // Создаем продукт с подготовленными данными
            $product = Product::createProduct($productData);

            // Создаем перевод продукта
            ProductTranslation::createProductTranslations($product, $validatedData);

            // Преобразование русских названий частей дня в английские
            $mealTypeMap = [
                'завтрак' => 'morning',
                'обед' => 'dinner',
                'ужин' => 'supper',
            ];

            if (isset($validatedData['part_of_day']) && isset($mealTypeMap[$validatedData['part_of_day']])) {
                $validatedData['part_of_day'] = $mealTypeMap[$validatedData['part_of_day']];
                Log::info('Part of day converted to English in createProductWithTranslationsAndConsumption',
                    ['converted' => $validatedData['part_of_day']]);
            }

            $consumption = FoodConsumption::createFoodConsumption($validatedData, $product);

            DB::commit();

            return [
                'consumption_id' => $consumption->id,
                'food_id' => $product->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in createProductWithTranslationsAndConsumption: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'validatedData' => $validatedData,
            ]);
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function createFoodConsumption(array $data): array
    {
        try {
            Log::info('createFoodConsumption data', $data);

            // Проверяем наличие продукта
            $product = Product::findOrFail($data['product_id']);

            // Преобразование русских названий частей дня в английские
            $mealTypeMap = [
                'завтрак' => 'morning',
                'обед' => 'dinner',
                'ужин' => 'supper',
            ];

            $partOfDay = $data['part_of_day'] ?? 'morning';

            // Если передано русское название, преобразуем в английское
            if (isset($mealTypeMap[$partOfDay])) {
                $partOfDay = $mealTypeMap[$partOfDay];
                Log::info('Part of day converted to English', ['original' => $data['part_of_day'], 'converted' => $partOfDay]);
            }

            // Здесь данные для создания записи о потреблении
            $consumptionData = [
                'user_id' => $data['user_id'],
                'product_id' => $product->id,
                'part_of_day' => $partOfDay,
                // Используем поле quantity, которое теперь передаётся с фронтенда
                'quantity' => $data['quantity'] ?? 0,
            ];

            Log::info('Consumption data before save', $consumptionData);

            // Создаем запись о потреблении
            $consumption = FoodConsumption::createFoodConsumption($consumptionData);

            return [
                'consumption_id' => $consumption->id,
                'food_id' => $product->id,
            ];
        } catch (\Exception $e) {
            Log::error('Error creating food consumption: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
            throw $e;
        }
    }
}
