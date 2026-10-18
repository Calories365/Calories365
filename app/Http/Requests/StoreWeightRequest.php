<?php

namespace App\Http\Requests;

use App\Models\BodyWeight;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The body weight of a day, typed on the diary page.
 */
class StoreWeightRequest extends FormRequest
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
            'date' => 'required|date_format:Y-m-d',
            'weight' => 'required|numeric|between:'.BodyWeight::MIN.','.BodyWeight::MAX,
        ];
    }
}
