<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\BodyWeight;
use App\Models\FoodConsumption;
use App\Models\User;
use App\Models\UserResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Blood sugar and insulin of a user's day (InsulinCurve) from what the diary
 * holds, with what each meal and each activity did, the totals of the day that
 * matter for them and what to change.
 *
 * A meal counts at the time told for it; otherwise at the time it was saved,
 * when that falls in its part of the day or within four hours after it, since a
 * lunch is often eaten late and saved right away; otherwise at the usual time of
 * the part, since a breakfast saved in the evening was eaten in the morning. An
 * activity without a time told ended when it was saved. The days before count
 * too: a late supper goes on after midnight, and a run of yesterday still helps.
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
    public const PARTS = [
        'morning' => [300, 660, 510],
        'dinner' => [660, 960, 810],
        'supper' => [960, 1440, 1170],
    ];

    /**
     * A meal saved this long after the end of its part was still eaten then.
     */
    private const SAVED_LATE_MINUTES = 240;

    /**
     * When nothing tells the time of an activity of a past day.
     */
    private const USUAL_ACTIVITY_MINUTE = 720;

    /**
     * Exercise counts for two days (InsulinCurve).
     */
    private const DAYS_BEFORE = 2;

    /**
     * EFSA: the population reference intake of protein for adults, g per kg of
     * body weight, and the adequate intake of fibre, g a day.
     */
    public const PROTEIN_PER_KG = 0.83;

    public const FIBRE_PER_DAY = 25;

    /**
     * The glycemic load of a day under which it is low and over which it is high
     * (University of Sydney, glycemicindex.com).
     */
    public const LOW_DAY_LOAD = 80;

    public const HIGH_DAY_LOAD = 120;

    /**
     * A dip below the fasting level worth telling, mmol/L.
     */
    private const NOTABLE_DIP = 0.3;

    /**
     * A meal of fast carbohydrates: a GI from this and a load from that, with
     * less protein than the limit.
     */
    private const FAST_MEAL_INDEX = 65;

    private const FAST_MEAL_LOAD = 20;

    private const FAST_MEAL_PROTEIN = 10;

    private const LATE_MEAL_MINUTE = 1200;

    private const MAX_TIPS = 4;

    private const NOTABLE_FACTOR = 2;

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user, string $date, string $locale): array
    {
        $day = Carbon::parse($date)->startOfDay();
        $lifestyle = $this->lifestyle($user);
        $meals = $this->meals($this->foods($user->id, $day, $locale));
        $activities = $this->activities($user->id, $day, $locale);
        $moved = $activities->map(static fn (array $activity) => [
            'minute' => $activity['minute'],
            'minutes' => $activity['minutes'],
            'met' => $activity['met'],
            'type' => $activity['type'],
        ])->values()->all();
        $curveMeals = $meals->map(static fn (array $meal) => ['minute' => $meal['minute']] + $meal['response'])->values()->all();

        $moments = range(0, self::MINUTES_IN_DAY, self::STEP);
        $levels = InsulinCurve::levels($curveMeals, $moved, $lifestyle, $moments);
        $insulinPeak = array_search(max($levels['insulin']), $levels['insulin'], true);
        $glucosePeak = array_search(max($levels['glucose']), $levels['glucose'], true);
        $now = $day->isToday() ? (int) floor($day->diffInMinutes(now())) : null;
        $nowMoment = $now === null ? null : $now - $now % self::STEP;

        $ofTheDay = $this->mealsOfTheDay($meals->filter(static fn (array $meal) => $meal['minute'] >= 0)->values(), $moved, $lifestyle);
        $activitiesOfTheDay = $this->activitiesOfTheDay($activities, $curveMeals, $moved, $lifestyle, $moments, $levels);
        $summary = $this->summary($user->id, $day, $ofTheDay, $activitiesOfTheDay, $levels);

        return [
            'date' => $day->toDateString(),
            'lifestyle' => $lifestyle,
            'lifestyle_known' => $user->lifestyle !== null,
            'base' => InsulinCurve::BASE_LEVEL,
            'zones' => ['medium' => InsulinCurve::MEDIUM_LEVEL, 'high' => InsulinCurve::HIGH_LEVEL],
            'glucose' => [
                'base' => InsulinCurve::GLUCOSE_BASE,
                'high' => InsulinCurve::GLUCOSE_HIGH,
                'very_high' => InsulinCurve::GLUCOSE_VERY_HIGH,
                'peak' => ['minute' => $glucosePeak, 'level' => $levels['glucose'][$glucosePeak]],
            ],
            'points' => array_map(static fn (int $minute) => [
                'minute' => $minute,
                'level' => $levels['insulin'][$minute],
                'glucose' => $levels['glucose'][$minute],
            ], $moments),
            'now' => $nowMoment === null ? null : [
                'minute' => $now,
                'level' => $levels['insulin'][$nowMoment],
                'glucose' => $levels['glucose'][$nowMoment],
            ],
            'peak' => ['minute' => $insulinPeak, 'level' => $levels['insulin'][$insulinPeak]],
            'meals' => $ofTheDay,
            'activities' => $activitiesOfTheDay,
            'summary' => $summary,
            'tips' => $this->tips($ofTheDay, $summary, $now),
            'estimated_foods' => $meals->filter(static fn (array $meal) => $meal['minute'] >= 0)
                ->sum(static fn (array $meal) => collect($meal['items'])->filter(static fn (array $item) => ! $item['index_known'])->count()),
        ];
    }

    /**
     * When each meal of the day was eaten, as the curve takes it: part of the
     * day => "HH:MM".
     *
     * @return array<string, string>
     */
    public function mealTimes(int $userId, string $date): array
    {
        $day = Carbon::parse($date)->startOfDay();

        return FoodConsumption::query()
            ->where('user_id', $userId)
            ->whereDate('consumed_at', $day)
            ->get()
            ->groupBy('part_of_day')
            ->map(fn (Collection $meals) => self::time((int) $meals->min(fn (FoodConsumption $meal) => $this->eatenMinute($meal, $day))))
            ->sortBy(static fn (string $time) => $time)
            ->all();
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
    private function foods(int $userId, Carbon $day, string $locale): Collection
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

                return [
                    'id' => $meal->id,
                    'meal' => Carbon::parse($meal->consumed_at)->toDateString().' '.$meal->part_of_day,
                    'part_of_day' => (string) $meal->part_of_day,
                    'minute' => $this->eatenMinute($meal, $day),
                    'time_told' => $meal->eaten_at !== null,
                    'name' => $product->translations->first()->name ?? '',
                    'grams' => (int) $meal->quantity,
                    'nova' => $product->nova,
                    'index_known' => $product->glycemic_index !== null,
                    'food' => [
                        'grams' => (float) $meal->quantity,
                        'calories' => (float) $product->calories,
                        'proteins' => (float) $product->proteins,
                        'fats' => (float) $product->fats,
                        'carbohydrates' => (float) $product->carbohydrates,
                        'fibers' => $product->fibers === null ? null : (float) $product->fibers,
                        'sugars' => $product->sugars === null ? null : (float) $product->sugars,
                        'glycemic_index' => $product->glycemic_index,
                        'insulin_index' => $product->insulin_index,
                    ],
                ];
            })
            ->values();
    }

    /**
     * The foods put together by meal: one part of one day, at the time of its
     * earliest food.
     *
     * @param  Collection<int, array<string, mixed>>  $foods
     * @return Collection<int, array<string, mixed>>
     */
    private function meals(Collection $foods): Collection
    {
        return $foods->groupBy('meal')
            ->map(static fn (Collection $items) => [
                'part_of_day' => $items->first()['part_of_day'],
                'minute' => (int) $items->min('minute'),
                'time_told' => $items->every('time_told'),
                'items' => $items->values()->all(),
                'response' => InsulinCurve::meal($items->pluck('food')->all()),
            ])
            ->sortBy('minute')
            ->values();
    }

    /**
     * Minutes from the midnight of the day; negative for the days before.
     */
    private function eatenMinute(FoodConsumption $meal, Carbon $day): int
    {
        if ($meal->eaten_at !== null) {
            return (int) round($day->diffInMinutes($meal->eaten_at, false));
        }

        $date = Carbon::parse($meal->consumed_at)->startOfDay();
        [$from, $to, $usual] = self::PARTS[$meal->part_of_day] ?? self::PARTS['dinner'];
        $saved = $meal->created_at !== null ? (int) $date->diffInMinutes($meal->created_at, false) : -1;
        $minute = $saved >= $from && $saved < $to + self::SAVED_LATE_MINUTES ? $saved : $usual;

        return $minute + (int) round($day->diffInMinutes($date, false));
    }

    /**
     * The activities of the day and of the two days before.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function activities(int $userId, Carbon $day, string $locale): Collection
    {
        return Activity::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$day->copy()->subDays(self::DAYS_BEFORE)->toDateString(), $day->toDateString()])
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
                    'type' => $activity->type,
                    'emoji' => ActivityCatalog::exists((string) $activity->type) ? ActivityCatalog::TYPES[$activity->type][2] : null,
                    'title' => $activity->title,
                    'minute' => $minute + (int) round($day->diffInMinutes($date, false)),
                    'minutes' => $activity->minutes,
                    'met' => $activity->met,
                    'calories' => $activity->calories,
                    'date' => $activity->date->toDateString(),
                ];
            })
            ->values();
    }

    /**
     * Each meal of the day: how high it lifted sugar and insulin, what made it
     * lift more or less, its foods with their shares.
     *
     * @param  Collection<int, array<string, mixed>>  $meals
     * @param  array<int, array{minute: int, minutes: int, met: float, type: string|null}>  $moved
     * @return array<int, array<string, mixed>>
     */
    private function mealsOfTheDay(Collection $meals, array $moved, int $lifestyle): array
    {
        return $meals->map(function (array $meal) use ($moved, $lifestyle) {
            $response = $meal['response'];
            $alone = [['minute' => $meal['minute']] + $response];
            $moments = range($meal['minute'], $meal['minute'] + 360, self::STEP);
            $levels = InsulinCurve::levels($alone, $moved, $lifestyle, $moments);
            $glucosePeak = array_search(max($levels['glucose']), $levels['glucose'], true);
            $insulinRise = max(0.0, max($levels['insulin']) - InsulinCurve::BASE_LEVEL);
            $glucoseRise = max(0.0, $levels['glucose'][$glucosePeak] - InsulinCurve::GLUCOSE_BASE);

            // The lowest sugar after the peak: the dip of a fast meal.
            $after = array_filter($levels['glucose'], static fn (int $moment) => $moment > $glucosePeak, ARRAY_FILTER_USE_KEY);
            $lowest = $after === [] ? null : array_search(min($after), $after, true);
            $dip = $lowest === null ? 0.0 : max(0.0, InsulinCurve::GLUCOSE_BASE - $after[$lowest]);

            // Movement after the meal, by the peak it would have had without it.
            $still = array_values(array_filter($moved, static fn (array $activity) => $meal['minute'] >= $activity['minute'] + $activity['minutes'] || $activity['minute'] >= $meal['minute'] + 180));
            $stillRise = max(InsulinCurve::levels($alone, $still, $lifestyle, $moments)['glucose']) - InsulinCurve::GLUCOSE_BASE;
            $context = InsulinCurve::context($meal['minute'], $moved, $lifestyle);

            $factors = [
                'proteins' => -$response['lowered']['proteins'],
                'fats' => -$response['lowered']['fats'],
                'fibers' => -$response['lowered']['fibers'],
                'evening' => (int) round(($context['evening'] - 1) * 100),
                'sleep' => (int) round(($context['sleep'] - 1) * 100),
                'exercise' => (int) round(($context['exercise'] - 1) * 100),
                'walk' => $stillRise > 0 ? -(int) round(($stillRise - $glucoseRise) / $stillRise * 100) : 0,
            ];

            $totalLoad = max(0.001, $response['glycemic_load']);
            $totalInsulin = max(0.001, $response['insulin_load']);

            return [
                'part_of_day' => $meal['part_of_day'],
                'minute' => $meal['minute'],
                'time_told' => $meal['time_told'],
                'rise' => round($insulinRise, 1),
                'glucose_peak' => round($levels['glucose'][$glucosePeak], 1),
                'glucose_peak_minute' => $glucosePeak,
                'glucose_rise' => round($glucoseRise, 1),
                'dip' => $dip >= self::NOTABLE_DIP ? ['minute' => $lowest, 'level' => round($after[$lowest], 1)] : null,
                'glycemic_load' => $response['glycemic_load'],
                'glycemic_index' => $response['glycemic_index'],
                'calories' => $response['calories'],
                'proteins' => $response['proteins'],
                'fats' => $response['fats'],
                'carbohydrates' => $response['carbohydrates'],
                'fibers' => $response['fibers'],
                'sugars' => $response['sugars'],
                // The way of life is the same for every meal and is shown once; a share under 2% is noise.
                'factors' => collect($factors)
                    ->filter(static fn (int $percent) => abs($percent) >= self::NOTABLE_FACTOR)
                    ->map(static fn (int $percent, string $key) => [
                        'key' => $key,
                        'percent' => $percent,
                        'label' => __('insulin.factors.'.$key, ['percent' => ($percent > 0 ? '+' : '−').abs($percent).'%']),
                    ])
                    ->values()
                    ->all(),
                'items' => collect($meal['items'])
                    ->map(static function (array $item) use ($glucoseRise, $insulinRise, $totalLoad, $totalInsulin) {
                        $load = InsulinCurve::glycemicLoad($item['food']);

                        return [
                            'id' => $item['id'],
                            'name' => $item['name'],
                            'grams' => $item['grams'],
                            'glycemic_load' => round($load, 1),
                            'glucose' => round($glucoseRise * $load / $totalLoad, 1),
                            'rise' => round($insulinRise * InsulinCurve::load($item['food']) / $totalInsulin, 1),
                            'calories' => round($item['food']['calories'] * $item['grams'] / 100),
                            'proteins' => round($item['food']['proteins'] * $item['grams'] / 100, 1),
                            'fibers' => $item['food']['fibers'] === null ? null : round($item['food']['fibers'] * $item['grams'] / 100, 1),
                            'nova' => $item['nova'],
                            'index_known' => $item['index_known'],
                        ];
                    })
                    ->sortByDesc('glucose')
                    ->values()
                    ->all(),
            ];
        })->all();
    }

    /**
     * Each activity of the day with how much less sugar and insulin the day
     * took because of it; a short night gives more, a negative share.
     *
     * @param  Collection<int, array<string, mixed>>  $activities
     * @param  array<int, array<string, mixed>>  $meals
     * @param  array<int, array{minute: int, minutes: int, met: float, type: string|null}>  $moved
     * @param  array<int, int>  $moments
     * @param  array{glucose: array<int, float>, insulin: array<int, float>}  $levels
     * @return array<int, array<string, mixed>>
     */
    private function activitiesOfTheDay(Collection $activities, array $meals, array $moved, int $lifestyle, array $moments, array $levels): array
    {
        $area = static fn (array $values, float $base) => array_sum(array_map(static fn (float $value) => max(0, $value - $base), $values));
        $glucose = $area($levels['glucose'], InsulinCurve::GLUCOSE_BASE);
        $insulin = $area($levels['insulin'], InsulinCurve::BASE_LEVEL);
        $share = static fn (float $otherwise, float $today) => $otherwise > 0 ? (int) round(($otherwise - $today) / $otherwise * 100) : 0;

        return $activities
            ->filter(static fn (array $activity) => $activity['minute'] + $activity['minutes'] > 0 && $activity['minute'] < self::MINUTES_IN_DAY)
            ->map(function (array $activity) use ($meals, $moved, $lifestyle, $moments, $area, $glucose, $insulin, $share) {
                $without = array_values(array_filter($moved, static fn (array $other) => $other['minute'] !== $activity['minute'] || $other['minutes'] !== $activity['minutes']));
                $otherwise = InsulinCurve::levels($meals, $without, $lifestyle, $moments);

                return [
                    'id' => $activity['id'],
                    'type' => $activity['type'],
                    'emoji' => $activity['emoji'],
                    'title' => $activity['title'],
                    'minute' => $activity['minute'],
                    'minutes' => $activity['minutes'],
                    'met' => $activity['met'],
                    'calories' => $activity['calories'],
                    'sleep' => $activity['type'] === ActivityCatalog::SLEEP,
                    'effect' => $share($area($otherwise['insulin'], InsulinCurve::BASE_LEVEL), $insulin),
                    'glucose_effect' => $share($area($otherwise['glucose'], InsulinCurve::GLUCOSE_BASE), $glucose),
                ];
            })
            ->sortBy('minute')
            ->values()
            ->all();
    }

    /**
     * What the day added up to: nutrients and their norms, how long sugar was
     * high, how the body moved and slept.
     *
     * @param  array<int, array<string, mixed>>  $meals
     * @param  array<int, array<string, mixed>>  $activities
     * @param  array{glucose: array<int, float>, insulin: array<int, float>}  $levels
     * @return array<string, mixed>
     */
    private function summary(int $userId, Carbon $day, array $meals, array $activities, array $levels): array
    {
        $meals = collect($meals);
        $sum = static fn (string $field) => round($meals->sum($field), 1);
        $weight = BodyWeight::latestFor($userId, $day->toDateString());
        $proteins = $sum('proteins');
        $load = $sum('glycemic_load');

        $items = $meals->flatMap(static fn (array $meal) => $meal['items']);
        $calories = (float) $meals->sum('calories');
        $ultra = (float) $items->where('nova', ProductQuality::ULTRA_PROCESSED)->sum('calories');

        $glucose = array_slice($levels['glucose'], 0, -1, true);
        $movement = collect($activities)->reject(static fn (array $activity) => $activity['sleep']);
        $afterMeals = 0;
        foreach (range(0, self::MINUTES_IN_DAY - 1) as $minute) {
            $moving = $movement->contains(static fn (array $activity) => $activity['met'] >= 2.5 && $minute >= $activity['minute'] && $minute < $activity['minute'] + $activity['minutes']);
            if ($moving && $meals->contains(static fn (array $meal) => $minute >= $meal['minute'] && $minute < $meal['minute'] + 180)) {
                $afterMeals++;
            }
        }
        $night = collect($activities)->filter(static fn (array $activity) => $activity['sleep'] && $activity['minutes'] >= 180)->max('minutes');

        return [
            'calories' => round($calories),
            'proteins' => $proteins,
            'fats' => $sum('fats'),
            'carbohydrates' => $sum('carbohydrates'),
            'fibers' => $sum('fibers'),
            'sugars' => $sum('sugars'),
            'glycemic_load' => $load,
            'glycemic_load_level' => $load >= self::HIGH_DAY_LOAD ? 'high' : ($load >= self::LOW_DAY_LOAD ? 'medium' : 'low'),
            'weight' => $weight,
            'proteins_per_kg' => $weight ? round($proteins / $weight, 2) : null,
            'proteins_target' => $weight ? (int) round(self::PROTEIN_PER_KG * $weight) : null,
            'fibers_target' => self::FIBRE_PER_DAY,
            // Foods whose fibre the database does not know yet (ProductEnricher gives it within minutes).
            'fibers_unknown' => $items->whereNull('fibers')->count(),
            'ultra_processed_share' => $calories > 0 && $items->whereNotNull('nova')->isNotEmpty() ? (int) round($ultra / $calories * 100) : null,
            'glucose_high_minutes' => count(array_filter($glucose, static fn (float $level) => $level >= InsulinCurve::GLUCOSE_HIGH)) * self::STEP,
            'glucose_spikes' => $meals->filter(static fn (array $meal) => $meal['glucose_peak'] >= InsulinCurve::GLUCOSE_HIGH)->count(),
            'moved_minutes' => (int) $movement->sum('minutes'),
            'active_minutes' => (int) $movement->where('met', '>=', 3.0)->sum('minutes'),
            'moved_after_meals_minutes' => $afterMeals,
            'active_calories' => (int) $movement->sum('calories'),
            'sleep_hours' => $night ? round($night / 60, 1) : null,
        ];
    }

    /**
     * What to change, the most telling first, in the user's language.
     *
     * @param  array<int, array<string, mixed>>  $meals
     * @param  array<string, mixed>  $summary
     * @return array<int, string>
     */
    private function tips(array $meals, array $summary, ?int $now): array
    {
        if ($meals === []) {
            return [__('insulin.tips.no_food')];
        }

        $meals = collect($meals);
        $name = static fn (array $meal) => __('insulin.parts.'.$meal['part_of_day']);
        $tips = [];

        $highest = $meals->sortByDesc('glucose_rise')->first();
        $walked = collect($highest['factors'])->contains('key', 'walk');
        if ($highest['glucose_rise'] >= 1.5 && ! $walked && ($now === null || $now >= $highest['minute'])) {
            $tips[] = __('insulin.tips.walk', ['meal' => $name($highest)]);
        }

        $dipping = $meals->first(static fn (array $meal) => $meal['dip'] !== null && ($now === null || $meal['dip']['minute'] >= $now - 60));
        if ($dipping !== null) {
            $tips[] = __('insulin.tips.dip', ['meal' => $name($dipping), 'time' => self::time($dipping['dip']['minute'])]);
        }

        $fast = $meals->first(static fn (array $meal) => ($meal['glycemic_index'] ?? 0) >= self::FAST_MEAL_INDEX
            && $meal['glycemic_load'] >= self::FAST_MEAL_LOAD
            && $meal['proteins'] < self::FAST_MEAL_PROTEIN);
        if ($fast !== null) {
            $tips[] = __('insulin.tips.fast_meal', ['meal' => $name($fast)]);
        }

        if ($meals->contains(static fn (array $meal) => $meal['minute'] >= self::LATE_MEAL_MINUTE && $meal['glycemic_load'] >= self::FAST_MEAL_LOAD)) {
            $tips[] = __('insulin.tips.evening');
        }

        if ($summary['sleep_hours'] !== null && $summary['sleep_hours'] < 7) {
            $tips[] = __('insulin.tips.sleep', ['hours' => self::number($summary['sleep_hours'])]);
        }

        // The norms of the day say something only once the day is mostly eaten.
        if ($now === null || $now >= 1080) {
            if ($summary['fibers'] < self::FIBRE_PER_DAY && $summary['fibers_unknown'] === 0) {
                $tips[] = __('insulin.tips.fibre', ['grams' => self::number($summary['fibers']), 'target' => self::FIBRE_PER_DAY]);
            }

            if ($summary['proteins_per_kg'] !== null && $summary['proteins_per_kg'] < self::PROTEIN_PER_KG) {
                $tips[] = __('insulin.tips.protein', ['grams' => self::number($summary['proteins']), 'per_kg' => self::number($summary['proteins_per_kg'])]);
            }

            if ($summary['glycemic_load'] >= self::HIGH_DAY_LOAD) {
                $tips[] = __('insulin.tips.load', ['load' => (int) round($summary['glycemic_load'])]);
            }
        }

        return array_slice($tips, 0, self::MAX_TIPS);
    }

    private static function time(int $minute): string
    {
        $inDay = (($minute % self::MINUTES_IN_DAY) + self::MINUTES_IN_DAY) % self::MINUTES_IN_DAY;

        return sprintf('%02d:%02d', intdiv($inDay, 60), $inDay % 60);
    }

    private static function number(float $value): string
    {
        $number = (string) round($value, 1);

        return app()->getLocale() === 'en' ? $number : str_replace('.', ',', $number);
    }
}
