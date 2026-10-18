<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A change of a meal draft item from the draft editor: the grams eaten, or the
 * name and nutrition facts per 100 g the user types.
 */
class UpdateDraftItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grams' => 'sometimes|numeric|min:1|max:5000',
            'name' => 'sometimes|string|min:1|max:150',
            'calories' => 'sometimes|numeric|min:0|max:950',
            'proteins' => 'sometimes|numeric|min:0|max:100',
            'fats' => 'sometimes|numeric|min:0|max:100',
            'carbohydrates' => 'sometimes|numeric|min:0|max:100',
        ];
    }
}
