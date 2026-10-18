<?php

namespace App\Http\Middleware;

use App\Services\BotPanelService;
use App\Services\MealDraftService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The spam guard for inputs to the meal draft from the site and the Mini App.
 *
 * Every input costs a model call; the panel counts them per account, shared
 * with the bot, which counts its own inputs before it calls the diary. A short
 * edit ("2 250") needs no model and is not counted.
 */
class GuardDraftInput
{
    private BotPanelService $botPanelService;

    public function __construct(BotPanelService $botPanelService)
    {
        $this->botPanelService = $botPanelService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $text = (string) $request->input('text', '');

        if (! $request->hasFile('photo') && ! $request->hasFile('audio') && MealDraftService::isShortEdit($text)) {
            return $next($request);
        }

        $allowed = $this->botPanelService->canTranscribeAudio((int) $request->user()->id);

        if ($allowed === null) {
            return response()->json(['message' => 'voice_service_unavailable'], 503);
        }

        if (! $allowed) {
            return response()->json(['message' => 'voice_rate_limited'], 429);
        }

        return $next($request);
    }
}
