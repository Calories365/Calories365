<?php

namespace App\Services;

use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\ProductTranslation;
use App\Traits\DoubleMetaphoneTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{
    use DoubleMetaphoneTrait;

    private ProductMatcher $matcher;

    public function __construct(ProductMatcher $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * Create a product from what the user saved, with its translations and the meal.
     *
     * Nutrition facts the model estimated and the user kept ($validatedData['ai_generated'])
     * make a shared product owned by nobody, source 'ai', and the name is kept as its
     * alias: the next user who names the food gets it from the database. Facts the user
     * typed in or changed make the user's own product, seen by no one else, which
     * replaces the user's earlier product of the same name. The facts stay the user's
     * own too when they cannot be true or a shared product of that name exists.
     *
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

            $productData = [];

            if (isset($validatedData['product']) && is_array($validatedData['product'])) {
                $productData = $validatedData['product'];
            }

            foreach (['calories', 'proteins', 'carbohydrates', 'fats', 'fibers'] as $field) {
                if (! isset($productData[$field]) && isset($validatedData[$field])) {
                    $productData[$field] = $validatedData[$field];
                }
            }

            $shared = ! empty($validatedData['ai_generated']) && $this->canBeShared($validatedData['name'], $productData, $locale);

            $validatedData['active'] = 1;
            $validatedData['verified'] = 0;

            if ($shared) {
                $product = Product::create($productData + ['user_id' => null, 'source' => Product::SOURCE_AI]);

                ProductTranslation::createProductTranslations($product, ['user_id' => null] + $validatedData);
                ProductAlias::remember($validatedData['name'], $locale, $product->id);
            } else {
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

                $product = Product::createProduct(['user_id' => $validatedData['user_id'] ?? auth()->id()] + $productData);

                ProductTranslation::createProductTranslations($product, $validatedData);
            }

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
     * Whether facts the model estimated may join the shared database under the name.
     *
     * @param  array<string, mixed>  $facts
     */
    private function canBeShared(string $name, array $facts, string $locale): bool
    {
        $plausible = $this->matcher->plausible(FoodName::parse($name), [
            'calories' => (float) ($facts['calories'] ?? 0),
            'proteins' => (float) ($facts['proteins'] ?? 0),
            'fats' => (float) ($facts['fats'] ?? 0),
            'carbohydrates' => (float) ($facts['carbohydrates'] ?? 0),
        ]);

        return $plausible && ! ProductTranslation::query()
            ->where('locale', $locale)
            ->where('name', $name)
            ->whereNull('user_id')
            ->exists();
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
