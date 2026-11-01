<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * When an activity began or how long it lasted, corrected on the page.
 */
class UpdateActivityRequest extends FormRequest
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
            'time' => ['nullable', 'required_without:minutes', 'date_format:H:i'],
            'minutes' => ['nullable', 'required_without:time', 'integer', 'min:1', 'max:960'],
        ];
    }
}
