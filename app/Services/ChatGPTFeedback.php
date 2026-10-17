<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatGPTFeedback
{
    /**
     * Key of the OpenAI project for the current locale.
     */
    private function getApiKey(): string
    {
        $keys = config('services.openai.keys');

        return (string) ($keys[app()->getLocale()] ?? $keys['ru'] ?? '');
    }

    /**
     * Ask the model for a reply to the prompt.
     *
     * Failures are thrown, not returned as text: the caller stores the result as
     * finished feedback, and an error message stored that way is never replaced.
     * The length of the reply is set by the prompt; the GPT-6 models reject
     * max_tokens and any temperature but the default.
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
                'model' => config('services.openai.models.feedback'),
                'reasoning_effort' => config('services.openai.reasoning_effort'),
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
