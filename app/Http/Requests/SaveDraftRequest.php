<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Saving the meal draft as a meal of the day.
 */
class SaveDraftRequest extends FormRequest
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
            'part_of_day' => 'required|in:morning,dinner,supper',
            'consumed_at' => 'required|date_format:Y-m-d',
        ];
    }
}
