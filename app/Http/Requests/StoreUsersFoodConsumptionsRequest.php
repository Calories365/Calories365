<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsersFoodConsumptionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'calories' => 'required|integer|min:0',
            'carbohydrates' => 'required|integer|min:0',
            'fats' => 'required|integer|min:0',
            'fibers' => 'required|integer|min:0',
            'proteins' => 'required|integer|min:0',
            'quantity' => 'required|integer|min:0',
            'consumed_at' => 'required|date',
            'part_of_day' => 'required|in:morning,dinner,supper',
        ];
    }
}
