<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Talks to the bot panel, which owns the Telegram bot tokens and the
 * telegram_id ↔ calories_id mapping.
 */
class BotPanelService
{
    private ?string $url;

    private ?string $key;

    private ?string $host;

    public function __construct()
    {
        $this->url = config('services.bot_panel.url');
        $this->key = config('services.bot_panel.key');
        $this->host = config('services.bot_panel.host');
    }

    /**
     * Ask the panel to verify a Mini App initData string.
     *
     * @return array<string, mixed>|null the panel payload, or null when the
     *                                   signature is invalid or the panel is unreachable
     */
    public function validateInitData(string $initData, ?string $bot = null): ?array
    {
        if (! $this->isConfigured()) {
            Log::warning('Bot panel URL or API key is not set');

            return null;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders($this->headers())
                ->post($this->url.'/api/telegram/web-app/validate', array_filter([
                    'init_data' => $initData,
                    'bot' => $bot,
                ]));

            if ($response->failed()) {
                return null;
            }

            $payload = $response->json();

            return ! empty($payload['valid']) ? $payload : null;
        } catch (\Throwable $e) {
            Log::error('Error validating Mini App init data: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Bind a Telegram account to a Calories365 account inside the panel.
     */
    public function linkTelegramUser(int $telegramId, int $caloriesId, ?string $locale = null, ?string $email = null, ?string $name = null): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('Bot panel URL or API key is not set');

            return false;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders($this->headers())
                ->post($this->url.'/api/telegram/web-app/link', array_filter([
                    'telegram_id' => $telegramId,
                    'calories_id' => $caloriesId,
                    'locale' => $locale,
                    'email' => $email,
                    'name' => $name,
                ]));

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Error linking Telegram user in bot panel: '.$e->getMessage());

            return false;
        }
    }

    private function isConfigured(): bool
    {
        return ! empty($this->url) && ! empty($this->key);
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Api-Key' => (string) $this->key,
        ];

        if (! empty($this->host)) {
            $headers['Host'] = $this->host;
        }

        return $headers;
    }
}
