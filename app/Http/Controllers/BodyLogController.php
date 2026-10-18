<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWeightRequest;
use App\Models\Activity;
use App\Services\BodyLog;
use Illuminate\Http\JsonResponse;

/**
 * The body weight and the activities of a day on the diary page; told by voice
 * or text they come through the meal draft (MealDraftService).
 *
 * Every answer is the day as BodyLog::day gives it.
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

    public function destroyActivity(int $activity): JsonResponse
    {
        $found = Activity::query()->where('user_id', auth()->id())->find($activity);

        if ($found === null) {
            return response()->json(['message' => 'activity_not_found'], 404);
        }

        $found->delete();

        return response()->json($this->body->day((int) auth()->id(), $found->date->toDateString()));
    }
}
