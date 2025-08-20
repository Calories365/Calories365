<?php

namespace App\Services\ChatGPTServices;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;

class FoodSelection
{
    private Client $client;

    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->client = new Client;
        $this->apiKey = $apiKey;
    }

    public function FoodSelection(string $filePath)
    {
        $multipartBody = new MultipartStream([
            [
                'name' => 'file',
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath),
            ],
            [
                'name' => 'model',
                'contents' => 'whisper-1',
            ],
        ]);

        $headers = [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'multipart/form-data; boundary='.$multipartBody->getBoundary(),
        ];

        $request = new Request('POST', 'https://api.openai.com/v1/audio/transcriptions', $headers, $multipartBody);

        try {
            $response = $this->client->send($request);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function analyzeFoodIntake(string $text)
    {
        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Анализ текста: \"$text\". Укажи список продуктов с количеством. Если количество не указано, используйте среднестатистический вес или порцию.
                        надо именно список продуктов и все, например текст: я сьел 100грамм каротошки и помилрк. Ты должен будешь на такой ответ вывеси список:
                        Картошка - 100грамм;
                        Помидор - 120грамм;
                        то есть без лишней информации
                        и надо все переводить в граммы, если не указано их количество, если например говориться что 3 яйца, ты должен будешь написать именно среднестатистическое количество грамм в 3 яйцах
                        если текст не содержит продуктов для списка - пиши: 'продуктов нет'",
                    ],
                ],
            ],
        ]);

        try {
            $result = json_decode($response->getBody()->getContents(), true);

            $output = $result['choices'][0]['message']['content'] ?? 'Не удалось извлечь данные.';

            return $output;
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
