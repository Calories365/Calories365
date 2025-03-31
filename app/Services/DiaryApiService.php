<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class DiaryApiService
{
    private Client $client;
    private string $apiUrl;
    private mixed $host;
    private string $diaryApiKey;

    public function __construct()
    {
        $this->diaryApiKey = config('services.diary_api.key');
        $this->apiUrl      = config('services.diary_api.url');
        $this->client      = new Client();
        $this->host        = config('services.diary_api.host');
    }

    /**
     *
     *
     * @param int|null    $caloriesId
     * @param string|null $locale
     * @return array
     */
    private function getHeaders($caloriesId = null, $locale = null, $admin = null): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'Host'         => $this->host,
            'X-Api-Key'    => $this->diaryApiKey,
        ];

        if (!empty($caloriesId)) {
            $headers['X-Calories-Id'] = $caloriesId;
        }

        if (!empty($locale)) {
            $headers['X-Locale'] = $locale;
        }

        if (!empty($admin)) {
            $headers['X-admin'] = true;
        }

        return $headers;
    }


    public function sendText(string $text, $calories_id, $locale)
    {
        try {
            $response = $this->client->post($this->apiUrl . '/caloriesEndPoint', [
                'headers' => $this->getHeaders($calories_id, $locale),
                'json'    => [
                    'text' => $text,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("Error sending text to diary service: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }


    public function getTheMostRelevantProduct(string $text, $calories_id, $locale)
    {
        try {
            $response = $this->client->post($this->apiUrl . '/caloriesEndPoint/getTheMostRelevantProduct', [
                'headers' => $this->getHeaders($calories_id, $locale),
                'json'    => [
                    'text' => $text,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("Error getting relevant product from diary service: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }


    public function saveProduct(array $data, $calories_id, $locale)
    {
        try {
            $payload = array_merge($data);

            $response = $this->client->post($this->apiUrl . '/caloriesEndPoint/saveProduct', [
                'headers' => $this->getHeaders($calories_id, $locale),
                'json'    => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("Error saving product to diary service: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }


    public function saveFoodConsumption(array $data, $calories_id, $locale)
    {
        try {
            $payload = array_merge($data);

            $response = $this->client->post($this->apiUrl . '/caloriesEndPoint/saveFoodConsumption', [
                'headers' => $this->getHeaders($calories_id, $locale),
                'json'    => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("Error saving food consumption to diary service: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }


    public function showUserStats($date, $partOfDay = false, $calories_id, $locale)
    {
        try {
            $url = $this->apiUrl . '/caloriesEndPoint/showUserStats/' . $date;
            if ($partOfDay) {
                $url .= '/' . $partOfDay;
            }

            $response = $this->client->get($url, [
                'headers' => $this->getHeaders($calories_id, $locale),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("Error retrieving user stats from diary service: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }


    public function deleteMeal($mealId, $calories_id, $locale)
    {
        try {
            $response = $this->client->delete($this->apiUrl . '/caloriesEndPoint/deleteMeal/' . $mealId, [
                'headers' => $this->getHeaders($calories_id, $locale),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("Error deleting meal in diary service: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function checkTelegramCode(string $code, int $telegram_id, string $locale = 'en')
    {
        try {
            $response = $this->client->post($this->apiUrl . '/caloriesEndPoint/checkTelegramCode', [
                'headers' => $this->getHeaders($telegram_id, $locale),
                'json'    => [
                    'code'        => $code,
                    'telegram_id' => $telegram_id
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("Error verifying telegram code: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    public function showUsersInfoForBot($calories_id,  string $locale = 'en')
    {
        try {
            $response = $this->client->get($this->apiUrl . '/caloriesEndPoint/user-for-bot', [
                'headers' => $this->getHeaders($calories_id, $locale),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("Error verifying telegram code: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     *
     *
     * @param array  $caloriesIds
     * @param string $locale
     * @return array
     */
    public function showUsersInfoForBotMultiple(array $caloriesIds, string $locale = 'en'): array
    {
        $idsParam = implode(',', $caloriesIds);

        $url = $this->apiUrl . '/caloriesEndPoint/users-for-bot-multiple?ids=' . $idsParam;

        try {
            $response = $this->client->get($url, [
                'headers' => $this->getHeaders(null, null, true),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("Error retrieving multiple users info: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    public function fetchAllCaloriesUsers(string $locale = 'en'): array
    {
        $url = $this->apiUrl . '/caloriesEndPoint/all-users';

        try {
            $response = $this->client->get($url, [
                'headers' => $this->getHeaders(null, null, true),
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("Error retrieving all users info: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Toggle Russian language setting
     *
     * @param bool $enabled
     * @return array
     */
    public function toggleRussianLanguage(bool $enabled): array
    {
        try {
            $response = $this->client->post($this->apiUrl . '/caloriesEndPoint/toggleRussianLanguage', [
                'headers' => $this->getHeaders(null, null, true),
                'json'    => [
                    'enabled' => $enabled,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("Error toggling Russian language: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
