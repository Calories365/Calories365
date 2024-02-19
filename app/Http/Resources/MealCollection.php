<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class MealCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request): array
    {
        return $this->collection->map(function ($meal) {
            $product = $meal->product;
            $translation = $product->translations->first();

            return [
                'id' => $meal->id,
                'user_id' => $meal->user_id,
                'quantity' => $meal->quantity,
                'part_of_day' => $meal->part_of_day,
                'food_id' => $meal->food_id,
                'consumed_at' => $meal->consumed_at,
                'calories' => $product->calories,
                'proteins' => $product->proteins,
                'carbohydrates' => $product->carbohydrates,
                'fats' => $product->fats,
                'fibers' => $product->fibers,
                'name' => $translation->name,
            ];
        })->toArray();
    }
}
