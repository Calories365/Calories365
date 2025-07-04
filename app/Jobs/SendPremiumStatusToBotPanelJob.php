<?php

namespace App\Jobs;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPremiumStatusToBotPanelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;


    public function __construct(User $user)
    {
        $this->user = $user;
    }


    public function handle()
    {
        $botPanelUrl = env('BOT_PANEL_URL');
        $botApiKey = env('BOT_API_KEY');
        $host = env('BOT_HOST');

        if (! $botPanelUrl || ! $botApiKey) {
            Log::warning('Bot panel URL or API key not set');

            return;
        }
        $payload = [
            'calories_id' => $this->user->id,
            'premium_until' => $this->user->premium_until,
        ];

        $client = new Client;
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Host' => $host,
            'X-Api-Key' => $botApiKey,
        ];

        try {
            $response = $client->post($botPanelUrl.'/api/update-premium-status', [
                'headers' => $headers,
                'json' => [
                    'payload' => $payload,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (GuzzleException $e) {
            Log::error('Error sending premium status to bot panel: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }
}
