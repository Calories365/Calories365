<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CalculationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'gender' => $this->gender,
            'birthYear' => $this->birthYear,
            'weight' => $this->weight,
            'height' => $this->height,
            'goalWeight' => $this->goalWeight,
            'fat' => $this->fat,
            'activity' => $this->activity,
            'goal' => $this->goal,
            'dailyCalories' => $this->dailyCalories,
            'checkboxActive' => $this->checkboxActive,
        ];
    }
}
