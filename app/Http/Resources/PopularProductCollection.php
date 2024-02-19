<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PopularProductCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Получаем первый перевод, соответствующий заданной локали
        $translation = $this->translations->first();

        return [
            'id' => $this->id,
            'calories' => $this->calories,
            'proteins' => $this->proteins,
            'carbohydrates' => $this->carbohydrates,
            'fats' => $this->fats,
            'fibers' => $this->fibers,
            'name' => $translation ? $translation->name : null,
        ];
    }
}
