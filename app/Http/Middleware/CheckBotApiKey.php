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

        if ($request->routeIs('caloriesEndPoint.checkTelegramCode')) {

            $locale = $request->header('X-Locale');
            if ($locale) {
                app()->setLocale($locale);
            }
            return $next($request);
        }

        $userId = $request->header('X-Calories-Id');
        if (!$userId) {
            return response()->json(['error' => 'Calories ID not provided'], Response::HTTP_UNAUTHORIZED);
        }

//        Log::info('Request URL: ' . $request->fullUrl());
//        Log::info('User id: ' . $userId);

        Auth::loginUsingId($userId);
//        Log::info('auth id: ' . auth()->id());

        $locale = $request->header('X-Locale');
        if ($locale) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
