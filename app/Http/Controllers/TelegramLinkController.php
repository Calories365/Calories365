<?php

namespace App\Http\Controllers;

use App\Models\TelegramCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TelegramLinkController extends Controller
{
    /**
     * Generating code for Telegram bot and returning ready link
     */
    public function getLink(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $locale = $request->input('locale', 'ua');

        $isAcademic = app('academic') === true;

        $newCode = Str::random(10);
        TelegramCode::where('user_id', $user->id)->delete();
        TelegramCode::create([
            'user_id' => $user->id,
            'telegram_code' => $newCode,
            'telegram_code_expire_at' => Carbon::now()->addMinutes(30),
        ]);

        if ($isAcademic) {
            $baseUrl = config('services.academic_telegram_bot_url');
        } else {
            $baseUrl = config('services.telegram_bot_url');
        }

        $finalLink = $baseUrl.'?start='.$newCode.'_'.$locale;

        return response()->json([
            'link' => $finalLink,
        ]);
    }

    public function checkTelegramCode(Request $request): \Illuminate\Http\JsonResponse
    {
        $code = $request->input('code');
        $telegram_id = $request->input('telegram_id');

        $telegramCode = TelegramCode::where('telegram_code', $code)->first();

        if (! $telegramCode) {
            return response()->json([
                'success' => false,
                'message' => 'Code not found or already used',
            ], 404);
        }

        if ($telegramCode->telegram_code_expire_at && now()->greaterThan($telegramCode->telegram_code_expire_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Code expired',
            ], 410);
        }

        $userId = $telegramCode->user_id;

        $user = User::find($userId);

        if ($user) {
            $user->telegram_id = $telegram_id;
            $user->save();
        }

        $telegramCode->delete();

        $premium = $user->isPremium();
        $premium_until = $user->premium_until;

        return response()->json([
            'success' => true,
            'user_id' => $userId,
            'email' => $user->email,
            'name' => $user->name,
            'premium' => $premium,
            'premium_until' => $premium_until,
        ]);
    }
}
