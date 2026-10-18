<?php

namespace App\Http\Controllers;

use App\Jobs\SendNewUserToBotPanelJob;
use App\Models\User;
use App\Services\BotPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Signs a Telegram Mini App visitor into Calories365.
 *
 * The Mini App runs inside Telegram's WebView, where there is no login form:
 * Telegram hands the page a signed initData string, the bot panel verifies it
 * against the bot token, and we trade it for a Sanctum token.
 */
class TelegramWebAppController extends Controller
{
    protected BotPanelService $botPanelService;

    public function __construct(BotPanelService $botPanelService)
    {
        $this->botPanelService = $botPanelService;
    }

    public function auth(Request $request): JsonResponse
    {
        $input = $request->validate([
            'init_data' => 'required|string',
            'bot' => 'nullable|string|max:255',
        ]);

        $bot = $input['bot'] ?? config('services.telegram_mini_app.bot');

        $panelResponse = $this->botPanelService->validateInitData($input['init_data'], $bot);

        if (! $panelResponse) {
            // The reason reaches the screen of the failed sign-in, to tell a
            // panel that is down or misconfigured from a bad signature.
            return response()->json([
                'message' => 'Invalid Telegram init data',
                'reason' => $this->botPanelService->initDataFailure(),
            ], 401);
        }

        $telegramUser = $panelResponse['user'];
        $telegramId = (int) $telegramUser['telegram_id'];
        $locale = $this->normalizeLocale($telegramUser['language_code'] ?? null);

        $user = User::where('telegram_id', $telegramId)->first();

        // The panel may already know the account from the /start CODE flow even
        // when telegram_id never made it into our users table.
        if (! $user && ! empty($panelResponse['calories_id'])) {
            $user = User::find($panelResponse['calories_id']);
        }

        $isNew = false;

        if (! $user) {
            $user = $this->createUserFromTelegram($telegramUser);
            $isNew = true;
        }

        if ((string) $user->telegram_id !== (string) $telegramId) {
            $user->telegram_id = $telegramId;
            $user->save();
        }

        if ($isNew || (int) ($panelResponse['calories_id'] ?? 0) !== $user->id) {
            $this->botPanelService->linkTelegramUser(
                $telegramId,
                $user->id,
                $locale,
                $user->email,
                $user->name
            );
        }

        // One live Mini App token per user — opening the app again replaces it.
        $user->tokens()->where('name', 'telegram-mini-app')->delete();
        $token = $user->createToken('telegram-mini-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'created' => $isNew,
            'locale' => $locale ?: $panelResponse['locale'] ?? null,
            'user' => array_merge(
                $user->only([
                    'email',
                    'name',
                    'email_verified_at',
                    'calories_limit',
                    'id',
                    'premium_until',
                ]),
                ['telegram_auth' => true]
            ),
        ]);
    }

    /**
     * Telegram gives us no email or password, so a Mini App account gets a
     * synthetic address and a random password it never needs to know.
     *
     * @param  array<string, mixed>  $telegramUser
     */
    private function createUserFromTelegram(array $telegramUser): User
    {
        $name = trim(($telegramUser['first_name'] ?? '').' '.($telegramUser['last_name'] ?? ''));

        if ($name === '') {
            $name = $telegramUser['username'] ?? 'Telegram user';
        }

        $user = User::create([
            'name' => $name,
            'email' => $this->syntheticEmail((int) $telegramUser['telegram_id']),
            'password' => Hash::make(Str::random(40)),
            'telegram_id' => (int) $telegramUser['telegram_id'],
        ]);

        // Nothing can be sent to a synthetic address, so treat it as verified.
        $user->forceFill(['email_verified_at' => now()])->save();

        SendNewUserToBotPanelJob::dispatch($user);

        return $user;
    }

    private function syntheticEmail(int $telegramId): string
    {
        $domain = config('services.telegram_mini_app.email_domain');

        $email = 'tg'.$telegramId.'@'.$domain;

        while (User::where('email', $email)->exists()) {
            $email = 'tg'.$telegramId.'-'.Str::lower(Str::random(6)).'@'.$domain;
        }

        return $email;
    }

    private function normalizeLocale(?string $languageCode): ?string
    {
        if (! $languageCode) {
            return null;
        }

        $locale = $languageCode === 'uk' ? 'ua' : $languageCode;

        return in_array($locale, ['en', 'ru', 'ua'], true) ? $locale : null;
    }
}
