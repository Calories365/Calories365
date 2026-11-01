<?php

namespace App\Http\Controllers;

use App\Http\Requests\SayActivityRequest;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\StoreWeightRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Activity;
use App\Services\ActivityCatalog;
use App\Services\ActivityReader;
use App\Services\AudioConversionService;
use App\Services\BodyLog;
use App\Services\InsulinDay;
use App\Services\MealDraftService;
use App\Services\SpeechToTextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * The body weight and the activities of a day on the site, in the Mini App and
 * in the bot: a kind picked with a tap, movement told in words or by voice
 * (sayActivity), or told together with a meal through the meal draft
 * (MealDraftService).
 *
 * Every answer about a day is the day as BodyLog::day gives it.
 */
class BodyLogController extends Controller
{
    public function __construct(private BodyLog $body) {}

    public function day(string $date): JsonResponse
    {
        return response()->json($this->body->day((int) auth()->id(), $date));
    }

    public function storeWeight(StoreWeightRequest $request): JsonResponse
    {
        $this->body->recordWeight((int) auth()->id(), $request->validated('date'), (float) $request->validated('weight'));

        return response()->json($this->body->day((int) auth()->id(), $request->validated('date')));
    }

    /**
     * Keep an activity of a kind of the catalogue, or typed with its intensity.
     */
    public function storeActivity(StoreActivityRequest $request): JsonResponse
    {
        $data = $request->validated();
        $type = $data['type'] ?? null;
        $title = trim((string) ($data['title'] ?? ''));

        $activity = $this->body->recordActivity(
            (int) auth()->id(),
            $data['date'],
            $title !== '' ? $title : ActivityCatalog::title((string) $type),
            (int) $data['minutes'],
            $type !== null ? ActivityCatalog::met($type) : ActivityCatalog::INTENSITIES[$data['intensity']],
            $data['time'] ?? null,
            $type,
        );

        return response()->json($this->body->day((int) auth()->id(), $activity->date->toDateString()) + ['activity' => $activity->toPayload()]);
    }

    /**
     * Keep the activities a text or a recording tells, at once.
     */
    public function sayActivity(SayActivityRequest $request, ActivityReader $reader, MealDraftService $drafts, InsulinDay $insulin): JsonResponse
    {
        $text = $request->validated('text');

        try {
            if ($request->hasFile('audio')) {
                $text = $drafts->transcribe($request->file('audio'), app(AudioConversionService::class), app(SpeechToTextService::class));

                if ($text === null) {
                    return response()->json(['message' => 'voice_not_recognized'], 422);
                }
            }

            $activities = $reader->read((string) $text, app()->getLocale(), $insulin->mealTimes((int) auth()->id(), today()->toDateString()));
        } catch (\Throwable $e) {
            Log::warning('Activity was not read', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'activity_not_read'], 503);
        }

        $recorded = array_map(fn (array $activity) => $this->body->recordActivity(
            (int) auth()->id(),
            today()->toDateString(),
            $activity['title'],
            $activity['minutes'],
            $activity['met'],
            $activity['time'],
            $activity['type'],
        )->toPayload(), $activities);

        return response()->json(['text' => $text, 'understood' => $recorded !== [], 'recorded' => $recorded]);
    }

    /**
     * Correct when an activity began or how long it lasted.
     */
    public function updateActivity(UpdateActivityRequest $request, int $activity): JsonResponse
    {
        $found = Activity::query()->where('user_id', auth()->id())->find($activity);

        if ($found === null) {
            return response()->json(['message' => 'activity_not_found'], 404);
        }

        $minutes = $request->validated('minutes');
        $this->body->updateActivity($found, $request->validated('time'), $minutes === null ? null : (int) $minutes);

        return response()->json($this->body->day((int) auth()->id(), $found->date->toDateString()) + ['activity' => $found->toPayload()]);
    }

    public function destroyActivity(int $activity): JsonResponse
    {
        $found = Activity::query()->where('user_id', auth()->id())->find($activity);

        if ($found === null) {
            return response()->json(['message' => 'activity_not_found'], 404);
        }

        $found->delete();

        return response()->json($this->body->day((int) auth()->id(), $found->date->toDateString()));
    }

    /**
     * The kinds of movement to pick from, in the user's language.
     */
    public function types(): JsonResponse
    {
        return response()->json(['types' => ActivityCatalog::presets(app()->getLocale())]);
    }
}
