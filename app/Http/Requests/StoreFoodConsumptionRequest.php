<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFoodConsumptionRequest extends FormRequest
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
            'food_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'consumed_at' => 'required|date_format:Y-m-d',
            'part_of_day' => 'required|in:morning,dinner,supper'
        ];
    }
}
