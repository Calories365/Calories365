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
//        return auth()->check();
        return true;
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
            'calories' => 'required|numeric|min:0',
            'carbohydrates' => 'required|numeric|min:0',
            'fats' => 'required|numeric|min:0',
            'fibers' => 'required|numeric|min:0',
            'proteins' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'consumed_at' => 'required|date',
            'part_of_day' => 'required|in:morning,dinner,supper',
            'user_id' => 'nullable|integer',
        ];
    }
}
