<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\BodyWeight;

/**
 * The body weight and the physical activities a user tells, by voice, text or
 * on the site. A weight said plainly ("вешу 82,4") is read without the model;
 * an activity comes from the meal model (MealInputReader) with its MET, and the
 * calories it burned above rest are counted here: (MET − 1) × weight × hours,
 * since the daily expenditure already holds the rest.
 */
class BodyLog
{
    /**
     * "вешу 82,4", "вес 82.4 кг", "вага 82", "82,4 кг".
     */
    private const WEIGHT = '/^(?:(?:мой\s+|мій\s+)?(?:вес|вага|weight)(?:\s+(?:сегодня|утром|сьогодні|зранку|today))?|вешу|важу|i\s+weigh)\s*[:—-]?\s*(\d{2,3}(?:[.,]\d{1,2})?)\s*(?:кг|kg|килограмм\p{L}*|кілограм\p{L}*)?\.?$|^(\d{2,3}(?:[.,]\d{1,2})?)\s*(?:кг|kg)\.?$/iu';

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
     * Keep an activity of the day with the calories it burned above rest.
     */
    public function recordActivity(int $userId, string $date, string $title, int $minutes, float $met): Activity
    {
        $weight = BodyWeight::latestFor($userId, $date) ?? 70.0;

        return Activity::create([
            'user_id' => $userId,
            'date' => $date,
            'title' => $title,
            'minutes' => $minutes,
            'met' => $met,
            'calories' => (int) round(max(0, $met - 1) * $weight * $minutes / 60),
        ]);
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
