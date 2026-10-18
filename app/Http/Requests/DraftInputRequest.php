<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * One input to the meal draft: text (a voice message already turned into text,
 * or typed), a photo of the dish or of a label, or both.
 */
class DraftInputRequest extends FormRequest
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
            'text' => 'nullable|string|max:2000|required_without:photo',
            'photo' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:10240|required_without:text',
        ];
    }
}
