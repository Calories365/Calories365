<?php

namespace App\Jobs;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNewUserToBotPanelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;

    /**
     * Данные о новом пользователе
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Логика, которую выполняет Job.
     */
    public function handle()
    {
        $botPanelUrl = env('BOT_PANEL_URL');
        $botApiKey   = env('BOT_API_KEY');
        $host   = env('BOT_HOST');

        if (!$botPanelUrl || !$botApiKey) {
            Log::warning('Bot panel URL или API key не настроены');
            return;
        }

        $payload = [
            'calories_id' => $this->user->id,
            'name'        => $this->user->name,
            'email'       => $this->user->email,
        ];


            $client = new Client();
            $headers = [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'Host'         => $host,
                'X-Api-Key'    => $botApiKey,
            ];
            try {
                $response = $client->post($botPanelUrl . '/api/sync-calories-user', [
                    'headers' => $headers,
                    'json'    => [
                        'payload' =>  $payload,
                    ],
                ]);

                return json_decode($response->getBody()->getContents(), true);
            } catch (GuzzleException $e) {
                Log::error("Error sending text to diary service: " . $e->getMessage());
                return ['error' => $e->getMessage()];
            }
    }
}
