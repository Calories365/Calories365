<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Movement or sleep told in words or recorded by voice.
 */
class SayActivityRequest extends FormRequest
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
            'text' => ['nullable', 'string', 'max:2000', 'required_without:audio'],
            // Browsers record webm or mp4 audio; Safari labels it video/mp4.
            'audio' => ['nullable', 'file', 'mimetypes:audio/webm,video/webm,audio/mp4,video/mp4,audio/x-m4a,audio/mpeg,audio/ogg', 'max:20480'],
        ];
    }
}
