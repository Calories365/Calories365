<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class SpeechToTextService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Определяем нужный ключ в зависимости от текущей локали
     */
    private function getApiKey(): string
    {
        $locale = app()->getLocale();

        Log::info('$locale: ' . $locale);

        switch ($locale) {
            case 'ua':
                return env('OPENAI_API_KEY_UK');

            case 'en':
                return env('OPENAI_API_KEY_EN');

            default:
                return env('OPENAI_API_KEY_RU');
        }
    }

    public function convertSpeechToText(string $filePath)
    {
        $multipartBody = new MultipartStream([
            [
                'name'     => 'file',
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath)
            ],
            [
                'name'     => 'model',
                'contents' => 'whisper-1'
            ]
        ]);

        $headers = [
            'Authorization' => 'Bearer ' . $this->getApiKey(),
            'Content-Type'  => 'multipart/form-data; boundary=' . $multipartBody->getBoundary()
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

    public function analyzeFoodIntake(string $text)
    {
        Log::info('prompt: ' . $text);

        $prompt = __('calories365-bot.prompt_analyze_food_intake', [
            'text' => $text,
        ]);

        try {
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getApiKey(),
                    'Content-Type'  => 'application/json'
                ],
                'json' => [
                    'model'    => 'gpt-4o',
                    'messages' => [
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ]
                    ]
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return $result['choices'][0]['message']['content']
                ?? __('calories365-bot.data_not_extracted');
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @throws GuzzleException
     */
    public function generateNewProductData(string $text)
    {
        $prompt = __('calories365-bot.prompt_generate_new_product_data', [
            'text' => $text,
        ]);

        try {
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getApiKey(),
                    'Content-Type'  => 'application/json'
                ],
                'json' => [
                    'model'    => 'gpt-4o',
                    'messages' => [
                        [
                            'role'    => 'user',
                            'content' => $prompt
                        ]
                    ]
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return $result['choices'][0]['message']['content']
                ?? __('calories365-bot.data_not_extracted');
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function chooseTheMostRelevantProduct(array $products)
    {
        try {
            $chosenProductName = '';

            for ($i = 0; $i < count($products); $i += 5) {
                $prompt = '';

                for ($j = $i; $j < $i + 5 && $j < count($products); $j++) {
                    $name    = $products[$j]['name'];
                    $details = $products[$j]['details'];

                    $productNames = array_map(function ($detail) {
                        return $detail['id'] . ' - ' . $detail['name'];
                    }, $details);

                    $prompt .= __('calories365-bot.prompt_choose_relevant_products_part', [
                            'name'         => $name,
                            'productNames' => implode(', ', $productNames),
                        ]) . ' ';
                }

                $prompt .= __('calories365-bot.prompt_choose_relevant_products_footer');

                $chosenProductName .= $this->askGPTForRelevance($prompt);
            }

        } catch (\Exception $e) {
            Log::error("Error in choosing product: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
        return true;
    }

    private function askGPTForRelevance($prompt)
    {
        try {
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getApiKey(),
                    'Content-Type'  => 'application/json'
                ],
                'json' => [
                    'model'    => 'gpt-4o',
                    'messages' => [
                        [
                            'role'    => 'user',
                            'content' => $prompt
                        ]
                    ]
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return $result['choices'][0]['message']['content']
                ?? __('calories365-bot.data_not_extracted');
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
