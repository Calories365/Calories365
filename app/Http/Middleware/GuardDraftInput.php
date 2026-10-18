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
 * A model call is counted; the panel counts them per account, shared with the
 * bot, which counts its own before it calls the diary. What is counted depends
 * on the route: "model" (an input read at once) counts all but a short edit
 * ("2 250"), which needs no model; "audio" (an input kept for the calculation)
 * counts only a recording, which is transcribed; "always" counts the calculation.
 */
class GuardDraftInput
{
    private BotPanelService $botPanelService;

    public function __construct(BotPanelService $botPanelService)
    {
        $this->botPanelService = $botPanelService;
    }

    public function handle(Request $request, Closure $next, string $counts = 'model'): Response
    {
        if (! $this->counts($request, $counts)) {
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

    private function counts(Request $request, string $counts): bool
    {
        return match ($counts) {
            'always' => true,
            'audio' => $request->hasFile('audio'),
            default => $request->hasFile('photo') || $request->hasFile('audio')
                || ! MealDraftService::isShortEdit((string) $request->input('text', '')),
        };
    }
}
