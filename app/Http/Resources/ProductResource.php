<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->product->id,
            'calories' => $this->product->calories,
            'proteins' => $this->product->proteins,
            'carbohydrates' => $this->product->carbohydrates,
            'fats' => $this->product->fats,
            'fibers' => $this->product->fibers,
            'user_id' => $this->product->user_id,
            'name' => $this->name,
        ];
    }
}
