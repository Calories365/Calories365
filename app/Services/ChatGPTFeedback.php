<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatGPTFeedback
{
    private function getApiKey(): string
    {
        return match (app()->getLocale()) {
            'ua' => env('OPENAI_API_KEY_UK'),
            'en' => env('OPENAI_API_KEY_EN'),
            default => env('OPENAI_API_KEY_RU'),
        };
    }

    public function generateNewProductData(string $prompt): string
    {
        try {
            $result = Http::timeout(45)
                ->retry(3, 500, fn ($e) => in_array(optional($e->response)->status(), [429, 500, 502, 503, 504], true))
                ->withHeaders(['Authorization' => 'Bearer '.$this->getApiKey()])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'temperature' => 0.2,
                    'max_tokens' => 300,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ])
                ->throw()
                ->json();

            return $result['choices'][0]['message']['content']
                ?? __('calories365-bot.data_not_extracted');
        } catch (\Throwable $e) {
            return __('calories365-bot.failed_to_get_feedback');
        }
    }
}
