<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

/**
 * Speech to a list of products, and nutrition facts of a product, through OpenAI.
 *
 * Models come from config('services.openai.models'): transcription for speech,
 * meal_analysis for the list of products, product for nutrition facts.
 */
class SpeechToTextService
{
    private Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client;
    }

    /**
     * Key of the OpenAI project for the current locale.
     */
    private function getApiKey(): string
    {
        $keys = config('services.openai.keys');

        return (string) ($keys[app()->getLocale()] ?? $keys['ru'] ?? '');
    }

    /**
     * Transcribe the recording and turn the text into "Name - N grams;" lines.
     *
     * @return string|array{error: string}|false false when the transcript has no text
     */
    public function convertSpeechToText(string $filePath)
    {
        $languageCode = match (app()->getLocale()) {
            'ua' => 'uk',
            'ru' => 'ru',
            'en' => 'en',
            default => 'uk',
        };

        $multipartBody = new MultipartStream([
            [
                'name' => 'file',
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath),
            ],
            [
                'name' => 'model',
                'contents' => config('services.openai.models.transcription'),
            ],
            [
                // gpt-transcribe takes a list of expected languages instead of "language".
                'name' => 'languages[]',
                'contents' => $languageCode,
            ],
        ]);

        $headers = [
            'Authorization' => 'Bearer '.$this->getApiKey(),
            'Content-Type' => 'multipart/form-data; boundary='.$multipartBody->getBoundary(),
        ];

        $request = new Request(
            'POST',
            'https://api.openai.com/v1/audio/transcriptions',
            $headers,
            $multipartBody
        );

        try {
            $response = $this->client->send($request);
            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['text'])) {
                return $this->analyzeFoodIntake($data['text']);
            }

            return false;
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @return string|array{error: string}
     */
    public function analyzeFoodIntake(string $text)
    {
        $prompt = __('calories365-bot.prompt_analyze_food_intake', [
            'text' => $text,
        ]);

        return $this->complete(config('services.openai.models.meal_analysis'), $prompt);
    }

    /**
     * @return string|array{error: string}
     */
    public function generateNewProductData(string $text)
    {
        $prompt = __('calories365-bot.prompt_generate_new_product_data', [
            'text' => $text,
        ]);

        return $this->complete(config('services.openai.models.product'), $prompt);
    }

    /**
     * One chat completion; the reply text, or ['error' => ...] when the call failed.
     *
     * @return string|array{error: string}
     */
    private function complete(string $model, string $prompt)
    {
        try {
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->getApiKey(),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'reasoning_effort' => config('services.openai.reasoning_effort'),
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return $result['choices'][0]['message']['content']
                ?? __('calories365-bot.data_not_extracted');
        } catch (GuzzleException $e) {
            Log::error('OpenAI request failed', ['model' => $model, 'error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }
}
