<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollectDraftInputRequest;
use App\Http\Requests\DraftInputRequest;
use App\Http\Requests\SaveDraftRequest;
use App\Http\Requests\UpdateDraftItemRequest;
use App\Models\MealDraft;
use App\Models\MealDraftInput;
use App\Services\AudioConversionService;
use App\Services\MealDraftService;
use App\Services\SpeechToTextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The user's meal draft, see MealDraftService: for the bot panel (bot API key)
 * and for the site and the Mini App (the "Приём пищи" page).
 *
 * Every answer carries the draft in the shape of MealDraft::toPayload, or null
 * when there is none; the answers about the inputs waiting for the calculation
 * also carry them (inputs) and the state of the calculation (calculation, see
 * MealDraftService::calculation).
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
        return response()->json($this->state(auth()->id()));
    }

    /**
     * Keep a text, a photo or a voice message for the calculation; text is the
     * words of a voice recording. A short edit of the draft applies at once, and
     * understood and not_found report it.
     */
    public function collect(CollectDraftInputRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $photo = $request->file('photo');
        $text = $request->validated('text');
        $kind = $photo ? MealDraftInput::KIND_PHOTO : ($request->validated('kind') ?? MealDraftInput::KIND_TEXT);

        try {
            if ($request->hasFile('audio')) {
                $text = $this->drafts->transcribe($request->file('audio'), app(AudioConversionService::class), app(SpeechToTextService::class));

                if ($text === null) {
                    return response()->json(['message' => 'voice_not_recognized'], 422);
                }

                $kind = MealDraftInput::KIND_VOICE;
            }

            $applied = $this->drafts->collect($userId, app()->getLocale(), $kind, $text, $photo);
        } catch (\LengthException) {
            return response()->json(['message' => 'too_many_inputs'], 422);
        } catch (\Throwable $e) {
            Log::warning('Meal draft input was not kept', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'draft_input_failed'], 503);
        }

        return response()->json($this->state($userId) + [
            'text' => $text,
            'understood' => $applied['understood'] ?? true,
            'not_found' => $applied['not_found'] ?? [],
        ]);
    }

    /**
     * A photo waiting for the calculation, for the page to show it.
     */
    public function photo(int $input): BinaryFileResponse|JsonResponse
    {
        $found = MealDraftInput::query()->where('user_id', auth()->id())->whereNotNull('photo_path')->find($input);

        if ($found === null) {
            return response()->json(['message' => 'input_not_found'], 404);
        }

        return response()->file(Storage::disk(MealDraftInput::DISK)->path($found->photo_path), [
            'Content-Type' => $found->photo_mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Drop an input waiting for the calculation.
     */
    public function destroyInput(int $input): JsonResponse
    {
        if (! $this->drafts->removeInput(auth()->id(), $input)) {
            return response()->json(['message' => 'input_not_found'], 404);
        }

        return response()->json($this->state(auth()->id()));
    }

    /**
     * Drop all the inputs waiting for the calculation.
     */
    public function destroyInputs(): JsonResponse
    {
        $this->drafts->clearInputs(auth()->id());

        return response()->json($this->state(auth()->id()));
    }

    /**
     * Start the calculation of the inputs waiting; the answer comes before the
     * model's, and show() tells when the calculation is done.
     */
    public function calculate(): JsonResponse
    {
        if ($this->drafts->calculate(auth()->id(), app()->getLocale()) === null) {
            return response()->json(['message' => 'nothing_to_calculate'], 422);
        }

        return response()->json($this->state(auth()->id()), 202);
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

        return response()->json($this->state(auth()->id()));
    }

    /**
     * The draft, the inputs waiting for the calculation and its state.
     *
     * @return array{draft: array<string, mixed>|null, inputs: array<int, array<string, mixed>>, calculation: array<string, mixed>|null}
     */
    private function state(int $userId): array
    {
        return [
            'draft' => MealDraft::openFor($userId)?->toPayload(),
            'inputs' => MealDraftInput::pendingFor($userId)->map->toPayload()->all(),
            'calculation' => $this->drafts->calculation($userId),
        ];
    }
}
