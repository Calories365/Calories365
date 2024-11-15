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
            $locale = 'ru';
            $user_id = 32;
            $builder = ProductTranslation::search($validatedData['name'])
                ->where('locale', $locale)
                ->where('active', 1)
                ->query(function ($query) use ($user_id) {
                    $query->where(function ($subQuery) use ($user_id) {
                        $subQuery->where('user_id', $user_id);
                    });
                });

            $product = $builder->first();

            if($product) {
                $product->update(['active' => false]);
            }

            $validatedData['active'] = 1;

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
