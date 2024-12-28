<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckBotApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $clientKey = $request->header('X-Api-Key');
        $serverKey = config('services.bot_api_key');

        if (!$serverKey || $clientKey !== $serverKey) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $telegramId = $request->header('X-Telegram-Id');
        if (!$telegramId) {
            return response()->json(['error' => 'Telegram ID not provided'], Response::HTTP_UNAUTHORIZED);
        }

        Log::info('Request URL: ' . $request->fullUrl());

        if ($request->routeIs('caloriesEndPoint.checkTelegramCode')) {

            $locale = $request->header('X-Locale');
            if ($locale) {
                app()->setLocale($locale);
            }
            return $next($request);
        }

        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        Auth::login($user);

        $locale = $request->header('X-Locale');
        if ($locale) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
