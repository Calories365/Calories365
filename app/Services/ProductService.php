<?php

namespace App\Services;

use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Traits\DoubleMetaphoneTrait;
use Illuminate\Support\Facades\DB;


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
            $product = Product::createProduct($validatedData);
            $doubleMetaphoneName = $this->customDoubleMetaphone($validatedData['name']);
            ProductTranslation::createProductTranslations($product, $validatedData, $doubleMetaphoneName);
            $consumption = FoodConsumption::createFoodConsumption($validatedData, $product);

            DB::commit();
            return ['consumption_id' => $consumption->id, 'food_id' => $product->id];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
