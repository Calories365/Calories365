<?php

namespace App\Services;

use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Traits\DoubleMetaphoneTrait;
use Illuminate\Support\Facades\Auth;
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
            $locale  = app()->getLocale();
            $user_id = auth()->id();

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

            $product = Product::createProduct($validatedData);

            ProductTranslation::createProductTranslations($product, $validatedData);

            $consumption = FoodConsumption::createFoodConsumption($validatedData, $product);

            DB::commit();

            return [
                'consumption_id' => $consumption->id,
                'food_id'        => $product->id
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


}

