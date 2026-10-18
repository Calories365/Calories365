<?php

namespace App\Http\Requests;

use App\Models\MealDraftInput;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One input kept for the calculation of the meal draft: a text, the words of
 * a voice message the bot already recognised (kind voice), a photo of a dish or
 * of a label with its caption, or a voice recording from the site.
 */
class CollectDraftInputRequest extends FormRequest
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
            'kind' => 'nullable|in:'.MealDraftInput::KIND_TEXT.','.MealDraftInput::KIND_VOICE,
            'text' => 'nullable|string|max:2000|required_without_all:photo,audio',
            'photo' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:10240',
            // Browsers record webm or mp4 audio; Safari labels it video/mp4.
            'audio' => 'nullable|file|mimetypes:audio/webm,video/webm,audio/mp4,video/mp4,audio/x-m4a,audio/mpeg,audio/ogg|max:20480',
        ];
    }
}
