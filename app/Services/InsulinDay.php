<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\FoodConsumption;
use App\Models\User;
use App\Models\UserResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The insulin curve of a user's day (InsulinCurve) from what the diary holds.
 *
 * A meal counts at the time told for it; otherwise at the time it was saved, when
 * that falls in its part of the day, since a breakfast saved at four in the
 * afternoon was eaten in the morning; otherwise at the usual time of the part. An
 * activity without a time told ended when it was saved. The day before counts too:
 * a late supper goes on after midnight, and yesterday's run still helps.
 */
class InsulinDay
{
    /**
     * Minutes between the points of the curve.
     */
    private const STEP = 5;

    private const MINUTES_IN_DAY = 1440;

    /**
     * Each part of the day: from, to, its usual time, in minutes of the day.
     */
    private const PARTS = [
        'morning' => [300, 660, 510],
        'dinner' => [660, 960, 810],
        'supper' => [960, 1440, 1170],
    ];

    /**
     * When nothing tells the time of an activity of a past day.
     */
    private const USUAL_ACTIVITY_MINUTE = 720;

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user, string $date, string $locale): array
    {
        $day = Carbon::parse($date)->startOfDay();
        $lifestyle = $this->lifestyle($user);
        $meals = $this->meals($user->id, $day, $locale);
        $activities = $this->activities($user->id, $day);

        $foods = $meals->map(static fn (array $item) => [
            'minute' => $item['minute'],
            'load' => $item['load'],
            'peak_minutes' => $item['peak_minutes'],
        ])->values()->all();
        $moved = $activities->map(static fn (array $activity) => [
            'minute' => $activity['minute'],
            'minutes' => $activity['minutes'],
            'met' => $activity['met'],
        ])->values()->all();

        $moments = range(0, self::MINUTES_IN_DAY, self::STEP);
        $levels = InsulinCurve::levels($foods, $moved, $lifestyle, $moments);
        $peakMinute = array_search(max($levels), $levels, true);
        $isToday = $day->isToday();
        $now = $isToday ? (int) floor($day->diffInMinutes(now())) : null;

        return [
            'date' => $day->toDateString(),
            'lifestyle' => $lifestyle,
            'lifestyle_known' => $user->lifestyle !== null,
            'base' => InsulinCurve::BASE_LEVEL,
            'zones' => ['medium' => InsulinCurve::MEDIUM_LEVEL, 'high' => InsulinCurve::HIGH_LEVEL],
            'points' => array_map(static fn (int $minute) => ['minute' => $minute, 'level' => $levels[$minute]], $moments),
            'now' => $now === null ? null : ['minute' => $now, 'level' => $levels[$now - $now % self::STEP]],
            'peak' => ['minute' => $peakMinute, 'level' => $levels[$peakMinute]],
            'meals' => $this->mealsOfTheDay($meals, $moved, $lifestyle),
            'activities' => $this->activitiesOfTheDay($activities, $foods, $moved, $lifestyle, $moments, $levels),
            'estimated_foods' => $meals->filter(static fn (array $item) => $item['minute'] >= 0 && ! $item['index_known'])->count(),
        ];
    }

    /**
     * The way of life the user chose, or told the calculator.
     */
    private function lifestyle(User $user): int
    {
        $level = $user->lifestyle ?? UserResult::query()->where('user_id', $user->id)->value('activity');

        return isset(InsulinCurve::LIFESTYLE_FACTORS[(int) $level]) ? (int) $level : InsulinCurve::DEFAULT_LIFESTYLE;
    }

    /**
     * The foods of the day and the day before, a minute counted from midnight.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function meals(int $userId, Carbon $day, string $locale): Collection
    {
        return FoodConsumption::query()
            ->with(['product.translations' => static fn ($query) => $query->where('locale', $locale)])
            ->where('user_id', $userId)
            ->whereBetween('consumed_at', [$day->copy()->subDay()->startOfDay(), $day->copy()->endOfDay()])
            ->orderBy('id')
            ->get()
            ->filter(static fn (FoodConsumption $meal) => $meal->product !== null)
            ->map(function (FoodConsumption $meal) use ($day) {
                $product = $meal->product;
                $food = [
                    'grams' => (float) $meal->quantity,
                    'calories' => (float) $product->calories,
                    'proteins' => (float) $product->proteins,
                    'fats' => (float) $product->fats,
                    'carbohydrates' => (float) $product->carbohydrates,
                    'sugars' => $product->sugars === null ? null : (float) $product->sugars,
                    'glycemic_index' => $product->glycemic_index,
                ];

                return [
                    'id' => $meal->id,
                    'part_of_day' => (string) $meal->part_of_day,
                    'minute' => $this->eatenMinute($meal, $day),
                    'time_told' => $meal->eaten_at !== null,
                    'name' => $product->translations->first()->name ?? '',
                    'grams' => (int) $meal->quantity,
                    'load' => InsulinCurve::load($food),
                    'peak_minutes' => InsulinCurve::peakMinutes($food),
                    'index_known' => $product->glycemic_index !== null,
                ];
            })
            ->values();
    }

    /**
     * Minutes from the midnight of the day; negative for the day before.
     */
    private function eatenMinute(FoodConsumption $meal, Carbon $day): int
    {
        if ($meal->eaten_at !== null) {
            return (int) round($day->diffInMinutes($meal->eaten_at, false));
        }

        $date = Carbon::parse($meal->consumed_at)->startOfDay();
        [$from, $to, $usual] = self::PARTS[$meal->part_of_day] ?? self::PARTS['dinner'];
        $saved = $meal->created_at !== null ? (int) $date->diffInMinutes($meal->created_at, false) : -1;
        $minute = $saved >= $from && $saved < $to ? $saved : $usual;

        return $minute + (int) round($day->diffInMinutes($date, false));
    }

    /**
     * The activities of the day and the day before.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function activities(int $userId, Carbon $day): Collection
    {
        return Activity::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$day->copy()->subDay()->toDateString(), $day->toDateString()])
            ->orderBy('id')
            ->get()
            ->map(function (Activity $activity) use ($day) {
                $date = Carbon::parse($activity->date)->startOfDay();

                if ($activity->started_at !== null) {
                    $minute = (int) round($date->diffInMinutes($activity->started_at, false));
                } else {
                    $saved = $activity->created_at !== null ? (int) $date->diffInMinutes($activity->created_at, false) - $activity->minutes : -1;
                    $minute = $saved >= 0 && $saved < self::MINUTES_IN_DAY ? $saved : self::USUAL_ACTIVITY_MINUTE;
                }

                return [
                    'id' => $activity->id,
                    'title' => $activity->title,
                    'minute' => $minute + (int) round($day->diffInMinutes($date, false)),
                    'minutes' => $activity->minutes,
                    'met' => $activity->met,
                    'calories' => $activity->calories,
                ];
            })
            ->values();
    }

    /**
     * Each meal of the day with how high it lifted the level, its foods with theirs.
     *
     * @param  Collection<int, array<string, mixed>>  $meals
     * @param  array<int, array{minute: int, minutes: int, met: float}>  $moved
     * @return array<int, array<string, mixed>>
     */
    private function mealsOfTheDay(Collection $meals, array $moved, int $lifestyle): array
    {
        $factor = InsulinCurve::LIFESTYLE_FACTORS[$lifestyle];

        return $meals
            ->filter(static fn (array $item) => $item['minute'] >= 0)
            ->groupBy('part_of_day')
            ->map(function (Collection $items, string $part) use ($moved, $lifestyle, $factor) {
                $minute = (int) $items->min('minute');
                $foods = $items->map(static fn (array $item) => ['minute' => $item['minute'], 'load' => $item['load'], 'peak_minutes' => $item['peak_minutes']])->values()->all();
                $moments = range($minute, $minute + 300, self::STEP);
                $alone = InsulinCurve::levels($foods, $moved, $lifestyle, $moments);

                return [
                    'part_of_day' => $part,
                    'minute' => $minute,
                    'time_told' => $items->every('time_told'),
                    'rise' => round(max(0, max($alone) - InsulinCurve::BASE_LEVEL), 1),
                    'items' => $items
                        ->map(static fn (array $item) => [
                            'id' => $item['id'],
                            'name' => $item['name'],
                            'grams' => $item['grams'],
                            'rise' => round(InsulinCurve::peakRise($item['load'], $item['peak_minutes']) * $factor, 1),
                            'index_known' => $item['index_known'],
                        ])
                        ->sortByDesc('rise')
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('minute')
            ->values()
            ->all();
    }

    /**
     * Each activity of the day with how much less insulin the day took because of it.
     *
     * @param  Collection<int, array<string, mixed>>  $activities
     * @param  array<int, array{minute: int, load: float, peak_minutes: float}>  $foods
     * @param  array<int, array{minute: int, minutes: int, met: float}>  $moved
     * @param  array<int, int>  $moments
     * @param  array<int, float>  $levels
     * @return array<int, array<string, mixed>>
     */
    private function activitiesOfTheDay(Collection $activities, array $foods, array $moved, int $lifestyle, array $moments, array $levels): array
    {
        $rise = static fn (array $day) => array_sum(array_map(static fn (float $level) => max(0, $level - InsulinCurve::BASE_LEVEL), $day));
        $today = $rise($levels);

        return $activities
            ->filter(static fn (array $activity) => $activity['minute'] >= 0)
            ->map(function (array $activity) use ($foods, $moved, $lifestyle, $moments, $rise, $today) {
                $without = array_values(array_filter($moved, static fn (array $other) => $other['minute'] !== $activity['minute'] || $other['minutes'] !== $activity['minutes']));
                $otherwise = $rise(InsulinCurve::levels($foods, $without, $lifestyle, $moments));

                return [
                    'id' => $activity['id'],
                    'title' => $activity['title'],
                    'minute' => $activity['minute'],
                    'minutes' => $activity['minutes'],
                    'calories' => $activity['calories'],
                    'effect' => $otherwise > 0 ? (int) round(max(0, $otherwise - $today) / $otherwise * 100) : 0,
                ];
            })
            ->values()
            ->all();
    }
}
