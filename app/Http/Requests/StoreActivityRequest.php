<?php

namespace App\Http\Requests;

use App\Services\ActivityCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An activity of a day: a kind of the catalogue picked with a tap, or a title
 * with an intensity typed on the page. Sleep may last up to sixteen hours,
 * anything else ten.
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
            'type' => ['nullable', 'required_without:intensity', Rule::in(array_keys(ActivityCatalog::TYPES))],
            'title' => ['nullable', 'required_without:type', 'string', 'max:150'],
            'minutes' => ['required', 'integer', 'min:1', 'max:'.($this->input('type') === ActivityCatalog::SLEEP ? 960 : 600)],
            'intensity' => ['nullable', 'required_without:type', Rule::in(array_keys(ActivityCatalog::INTENSITIES))],
            'time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
