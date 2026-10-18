<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * One input to the meal draft: text (typed, or a voice message the bot already
 * turned into text), a photo of the dish or of a label, or a voice recording
 * from the site; a photo may come with text.
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
            'text' => 'nullable|string|max:2000|required_without_all:photo,audio',
            'photo' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:10240',
            // Browsers record webm or mp4 audio; Safari labels it video/mp4.
            'audio' => 'nullable|file|mimetypes:audio/webm,video/webm,audio/mp4,video/mp4,audio/x-m4a,audio/mpeg,audio/ogg|max:20480',
        ];
    }
}
