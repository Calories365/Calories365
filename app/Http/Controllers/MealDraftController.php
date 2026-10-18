<?php

namespace App\Http\Controllers;

use App\Http\Requests\DraftInputRequest;
use App\Http\Requests\SaveDraftRequest;
use App\Models\MealDraft;
use App\Services\MealDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * The meal draft of the account the bot panel acts for, see MealDraftService.
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
     * Apply a text or a photo to the draft. understood is false when the input
     * says nothing about food; not_found lists the foods without a product.
     */
    public function input(DraftInputRequest $request): JsonResponse
    {
        $photo = $request->file('photo');

        try {
            $result = $this->drafts->input(
                auth()->id(),
                app()->getLocale(),
                $request->validated('text'),
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
        ]);
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
