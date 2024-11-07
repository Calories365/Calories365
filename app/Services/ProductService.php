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
            $validatedData['user_id'] = $validatedData['user_id'] ?? auth()->id();
            Log::info('айди: ' . $validatedData['user_id']);
            $product = Product::createProduct($validatedData);
            ProductTranslation::createProductTranslations($product, $validatedData);
            $consumption = FoodConsumption::createFoodConsumption($validatedData, $product);
            DB::commit();
            return ['consumption_id' => $consumption->id, 'food_id' => $product->id];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
