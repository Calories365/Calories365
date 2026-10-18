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

    /**
     * Why the last initData check failed: not_configured, unreachable or
     * panel_{HTTP status}.
     */
    private ?string $initDataFailure = null;

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
        $this->initDataFailure = null;

        if (! $this->isConfigured()) {
            Log::warning('Bot panel URL or API key is not set');
            $this->initDataFailure = 'not_configured';

            return null;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders($this->headers())
                ->post($this->url.'/api/telegram/web-app/validate', array_filter([
                    'init_data' => $initData,
                    'bot' => $bot,
                ]));

            $payload = $response->json();

            if ($response->failed() || empty($payload['valid'])) {
                $this->initDataFailure = 'panel_'.$response->status();
                Log::warning('Bot panel refused Mini App init data', ['status' => $response->status()]);

                return null;
            }

            return $payload;
        } catch (\Throwable $e) {
            Log::error('Error validating Mini App init data: '.$e->getMessage());
            $this->initDataFailure = 'unreachable';

            return null;
        }
    }

    public function initDataFailure(): ?string
    {
        return $this->initDataFailure;
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

    /**
     * Ask the panel whether the user may record one more voice message.
     *
     * The panel owns the free limit shared with the bot, and counts the use.
     *
     * @return bool|null null when the panel is not configured or did not answer
     */
    public function canTranscribeAudio(int $caloriesId): ?bool
    {
        if (! $this->isConfigured()) {
            Log::warning('Bot panel URL or API key is not set');

            return null;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders($this->headers())
                ->get($this->url.'/api/subscription-check/'.$caloriesId);
        } catch (\Throwable $e) {
            Log::error('Error checking the voice limit in bot panel: '.$e->getMessage());

            return null;
        }

        $allowed = $response->successful() ? $response->json('canTranscribeAudio') : null;

        if (! is_bool($allowed)) {
            Log::error('Bot panel gave no voice limit answer', ['status' => $response->status()]);

            return null;
        }

        return $allowed;
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
