<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DateValidationRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'date' => 'required|date_format:Y-m-d',
            'partOfDay' => 'nullable|in:morning,dinner,supper',
        ];
    }

    public function validationData(): array
    {
        $allData = parent::validationData();

        if ($this->route('date')) {
            $allData['date'] = $this->route('date');
        }
        if ($this->route('partOfDay')) {
            $allData['partOfDay'] = $this->route('partOfDay');
        }
        return $allData;
    }

}
