<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * When a meal of a day was eaten, set on the insulin page.
 */
class StoreMealTimeRequest extends FormRequest
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
            'date' => ['required', 'date_format:Y-m-d'],
            'part_of_day' => ['required', 'in:morning,dinner,supper'],
            'time' => ['required', 'date_format:H:i'],
        ];
    }
}
