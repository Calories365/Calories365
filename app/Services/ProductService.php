<?php

namespace App\Services;

use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * @throws \Exception
     */
    public function createProductWithTranslationsAndConsumption($validatedData): array
    {
        DB::beginTransaction();
        try {
            $product = Product::createProduct($validatedData);
            ProductTranslation::createProductTranslation($product, $validatedData);
            $consumption = FoodConsumption::createFoodConsumption($product, $validatedData);

            DB::commit();
            return ['consumption_id' => $consumption->id, 'food_id' => $product->id];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
