<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserDataRequest extends FormRequest
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
            'gender' => 'required|string',
            'birthYear' => 'required',
            'weight' => 'required|numeric',
            'height' => 'required|numeric',
            'goalWeight' => 'required|numeric',
            'fat' => 'required|numeric',
            'activity' => 'required|integer',
            'goal' => 'required|integer',
            'dailyCalories' => 'required|integer',
            'checkboxActive' => 'required',
        ];
    }
}
