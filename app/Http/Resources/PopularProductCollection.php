<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PopularProductCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request): array
    {
        return $this->collection->map(function ($product) {
            $translation = $product->translations->first();

            return [
                'id' => $product->id,
                'calories' => $product->calories,
                'proteins' => $product->proteins,
                'carbohydrates' => $product->carbohydrates,
                'fats' => $product->fats,
                'fibers' => $product->fibers,
                'name' => $translation ? $translation->name : null,
                'is_popular' => $product->is_popular
            ];
        })->toArray(); // Преобразование коллекции в массив
    }

}

