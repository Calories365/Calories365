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

            if (! isset($validatedData['name']) && isset($validatedData['product_translation']['name'])) {
                $validatedData['name'] = $validatedData['product_translation']['name'];
            }

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

            $productData = [
                'user_id' => $validatedData['user_id'] ?? auth()->id(),
            ];

            if (isset($validatedData['product']) && is_array($validatedData['product'])) {
                $productData = array_merge($productData, $validatedData['product']);
            }

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

            $product = Product::createProduct($productData);

            ProductTranslation::createProductTranslations($product, $validatedData);

            $mealTypeMap = [
                'завтрак' => 'morning',
                'обед' => 'dinner',
                'ужин' => 'supper',
            ];

            if (isset($validatedData['part_of_day']) && isset($mealTypeMap[$validatedData['part_of_day']])) {
                $validatedData['part_of_day'] = $mealTypeMap[$validatedData['part_of_day']];
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

            $product = Product::findOrFail($data['product_id']);

            $mealTypeMap = [
                'завтрак' => 'morning',
                'обед' => 'dinner',
                'ужин' => 'supper',
            ];

            $partOfDay = $data['part_of_day'] ?? 'morning';

            if (isset($mealTypeMap[$partOfDay])) {
                $partOfDay = $mealTypeMap[$partOfDay];
            }

            $consumptionData = [
                'user_id' => $data['user_id'],
                'product_id' => $product->id,
                'part_of_day' => $partOfDay,
                'quantity' => $data['quantity'] ?? 0,
            ];

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
