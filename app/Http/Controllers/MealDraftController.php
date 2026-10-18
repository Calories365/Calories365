<?php

namespace App\Http\Controllers;

use App\Http\Requests\DraftInputRequest;
use App\Http\Requests\SaveDraftRequest;
use App\Http\Requests\UpdateDraftItemRequest;
use App\Models\MealDraft;
use App\Services\AudioConversionService;
use App\Services\MealDraftService;
use App\Services\SpeechToTextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * The user's meal draft, see MealDraftService: for the bot panel (bot API key)
 * and for the site and the Mini App (the "Приём пищи" page).
 *
 * Every answer carries the draft in the shape of MealDraft::toPayload, or null
 * when there is none.
 */
class MealDraftController extends Controller
{
    private MealDraftService $drafts;

    public function __construct(MealDraftService $drafts)
    {
        $this->drafts = $drafts;
    }

    public function show(): JsonResponse
    {
        return response()->json(['draft' => MealDraft::openFor(auth()->id())?->toPayload()]);
    }

    /**
     * Apply a text, a photo or a voice recording to the draft. understood is
     * false when the input says nothing about food; not_found lists the foods
     * without a product.
     */
    public function input(DraftInputRequest $request): JsonResponse
    {
        $photo = $request->file('photo');
        $text = $request->validated('text');

        try {
            if ($request->hasFile('audio')) {
                $text = $this->drafts->transcribe($request->file('audio'), app(AudioConversionService::class), app(SpeechToTextService::class));

                if ($text === null) {
                    return response()->json(['message' => 'voice_not_recognized'], 422);
                }
            }

            $result = $this->drafts->input(
                auth()->id(),
                app()->getLocale(),
                $text,
                $photo ? ['mime' => (string) $photo->getMimeType(), 'contents' => $photo->get()] : null,
            );
        } catch (\Throwable $e) {
            Log::warning('Meal draft input failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'draft_input_failed'], 503);
        }

        return response()->json([
            'draft' => $result['draft']?->toPayload(),
            'understood' => $result['understood'],
            'not_found' => $result['not_found'],
            'text' => $text,
        ]);
    }

    /**
     * Change an item from the draft editor.
     */
    public function updateItem(UpdateDraftItemRequest $request, int $item): JsonResponse
    {
        $draft = $this->drafts->updateItem(auth()->id(), $item, $request->validated());

        if ($draft === null) {
            return response()->json(['message' => 'item_not_found'], 404);
        }

        return response()->json(['draft' => $draft->toPayload()]);
    }

    /**
     * Remove an item from the draft editor.
     */
    public function destroyItem(int $item): JsonResponse
    {
        $draft = $this->drafts->removeItem(auth()->id(), $item);

        if ($draft === false) {
            return response()->json(['message' => 'item_not_found'], 404);
        }

        return response()->json(['draft' => $draft?->toPayload()]);
    }

    /**
     * Save the draft as a meal of the day and close it; total is the meal's.
     */
    public function save(SaveDraftRequest $request): JsonResponse
    {
        $total = $this->drafts->save(auth()->id(), $request->validated('part_of_day'), $request->validated('consumed_at'));

        if ($total === null) {
            return response()->json(['message' => 'draft_not_found'], 404);
        }

        return response()->json(['total' => $total]);
    }

    public function destroy(): JsonResponse
    {
        $this->drafts->cancel(auth()->id());

        return response()->json(['draft' => null]);
    }
}
