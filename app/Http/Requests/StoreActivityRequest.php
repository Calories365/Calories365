<?php

namespace App\Http\Requests;

use App\Services\BodyLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An activity of a day, typed on the insulin page.
 */
class StoreActivityRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:150'],
            'minutes' => ['required', 'integer', 'between:1,600'],
            'intensity' => ['required', Rule::in(array_keys(BodyLog::INTENSITIES))],
            'time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
