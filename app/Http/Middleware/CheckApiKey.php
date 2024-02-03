<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

//        if (!$apiKey || !ApiKey::where('key', $apiKey)->exists()) {
//            return response()->json(['error' => 'Invalid API Key'], 401);
//        }

        if (!$apiKey || !$apiKey != 1488) {
            return response()->json(['error' => 'Invalid API Key'], 401);
        }

        return $next($request);
    }

}
