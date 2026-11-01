<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\BodyWeight;
use Illuminate\Support\Carbon;

/**
 * The body weight and the physical activities a user tells, by voice, text or
 * on the site. A weight said plainly ("вешу 82,4") is read without the model;
 * an activity comes from a model (MealInputReader, ActivityReader) or a tap on a
 * kind of the catalogue (ActivityCatalog) with its MET, and the calories it
 * burned above rest are counted here: (MET − 1) × weight × hours, since the
 * daily expenditure already holds the rest.
 *
 * An activity of today without a time told ended when it was told; a night's
 * sleep told late in the day ended at the usual waking time. A time told that
 * is still to come today was yesterday: "лёг в 23" said in the morning.
 */
class BodyLog
{
    /**
     * "вешу 82,4", "вес 82.4 кг", "вага 82", "82,4 кг".
     */
    private const WEIGHT = '/^(?:(?:мой\s+|мій\s+)?(?:вес|вага|weight)(?:\s+(?:сегодня|утром|сьогодні|зранку|today))?|вешу|важу|i\s+weigh)\s*[:—-]?\s*(\d{2,3}(?:[.,]\d{1,2})?)\s*(?:кг|kg|килограмм\p{L}*|кілограм\p{L}*)?\.?$|^(\d{2,3}(?:[.,]\d{1,2})?)\s*(?:кг|kg)\.?$/iu';

    /**
     * Sleep told after this hour without a time ended at the usual waking time.
     */
    private const SLEEP_TOLD_LATE_HOUR = 11;

    private const USUAL_WAKING = '07:00';

    /**
     * A time told this far ahead of now is taken for yesterday's.
     */
    private const FUTURE_GRACE_MINUTES = 15;

    /**
     * The weight a text says plainly, or null.
     */
    public static function weightIn(string $text): ?float
    {
        if (! preg_match(self::WEIGHT, mb_strtolower(trim($text)), $match)) {
            return null;
        }

        $weight = (float) str_replace(',', '.', $match[1] !== '' ? $match[1] : $match[2]);

        return $weight >= BodyWeight::MIN && $weight <= BodyWeight::MAX ? $weight : null;
    }

    /**
     * Keep the weight of the day; a later one the same day replaces it.
     */
    public function recordWeight(int $userId, string $date, float $weight): BodyWeight
    {
        return BodyWeight::updateOrCreate(['user_id' => $userId, 'date' => $date], ['weight' => round($weight, 2)]);
    }

    /**
     * Keep an activity of the day with the calories it burned above rest; time is
     * when it began, H:i, when told; type is its kind in ActivityCatalog.
     */
    public function recordActivity(int $userId, string $date, string $title, int $minutes, float $met, ?string $time = null, ?string $type = null): Activity
    {
        $weight = BodyWeight::latestFor($userId, $date) ?? 70.0;
        $start = $this->startedAt($date, $minutes, $time, $type);

        return Activity::create([
            'user_id' => $userId,
            // An activity belongs to the day it ended: yesterday's evening walk, last night's sleep to this morning.
            'date' => $start?->copy()->addMinutes($minutes)->toDateString() ?? $date,
            'started_at' => $start,
            'type' => $type,
            'title' => $title,
            'minutes' => $minutes,
            'met' => $met,
            'calories' => (int) round(max(0, $met - 1) * $weight * $minutes / 60),
        ]);
    }

    /**
     * Change when an activity began or how long it lasted, keeping its calories
     * in step.
     */
    public function updateActivity(Activity $activity, ?string $time, ?int $minutes): Activity
    {
        $minutes ??= $activity->minutes;
        $calories = $activity->minutes > 0 ? $activity->calories * $minutes / $activity->minutes : $activity->calories;
        $date = $activity->date->toDateString();

        $activity->update([
            'started_at' => $time !== null
                ? $this->startedAt($date, $minutes, $time, $activity->type)
                : $activity->started_at,
            'minutes' => $minutes,
            'calories' => (int) round($calories),
        ]);

        return $activity;
    }

    private function startedAt(string $date, int $minutes, ?string $time, ?string $type): ?Carbon
    {
        $today = $date === today()->toDateString();

        if ($time !== null) {
            $start = Carbon::parse($date.' '.$time);

            return $today && $start->gt(now()->addMinutes(self::FUTURE_GRACE_MINUTES)) ? $start->subDay() : $start;
        }

        if (! $today) {
            return null;
        }

        $ended = $type === ActivityCatalog::SLEEP && now()->hour >= self::SLEEP_TOLD_LATE_HOUR
            ? Carbon::parse($date.' '.self::USUAL_WAKING)
            : now();

        return $ended->copy()->subMinutes($minutes)->startOfMinute();
    }

    /**
     * The weight told for the day, its activities and the calories they burned.
     *
     * @return array{weight: float|null, activities: array<int, array<string, mixed>>, calories: int}
     */
    public function day(int $userId, string $date): array
    {
        $activities = Activity::query()->where('user_id', $userId)->where('date', $date)->orderBy('id')->get();

        return [
            'weight' => BodyWeight::query()->where('user_id', $userId)->where('date', $date)->value('weight'),
            'activities' => $activities->map->toPayload()->all(),
            'calories' => (int) $activities->sum('calories'),
        ];
    }
}
