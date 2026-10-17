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

    /**
     * Ask the model for a reply to the prompt.
     *
     * Failures are thrown, not returned as text: the caller stores the result as
     * finished feedback, and an error message stored that way is never replaced.
     *
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \RuntimeException
     */
    public function generateNewProductData(string $prompt): string
    {
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

        $content = $result['choices'][0]['message']['content'] ?? null;

        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('OpenAI returned no feedback text');
        }

        return $content;
    }
}
