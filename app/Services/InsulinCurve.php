<?php

namespace App\Services;

/**
 * An estimate of how a person's insulin moves over a day, from what they ate and
 * how they moved. Nothing is measured: the level is a relative index in which 100
 * is the peak after 1000 kJ of glucose (about 62 g) in a moderately active person.
 *
 * What a food demands comes from the regression of Bell et al. 2016 (Nutrients 8,
 * 210), fitted on the measured insulin index of 147 foods, 1000 kJ portions:
 *
 *     FII = -4.2 + 0.9 glucose score + 0.3 sugar + 0.5 protein + 0.4 fat
 *
 * The glucose score of a portion is its glycemic load against 62.5 g of glucose,
 * and FII × kJ / 1000 gives the demand of any portion, in the terms of what the
 * diary knows (load()).
 *
 * The shape is a curve rising to a peak and falling back within about four hours.
 * The same demand comes faster and higher from a fast food. A crossover study of 10
 * healthy adults put the peak at about 42 minutes after a morning meal, and another
 * of high and low GI meals gave 65 against 48 μU/mL at the peak. The minutes to the
 * peak by GI and fat in peakMinutes() are this model's assumption around those.
 *
 * Moving: 28 minutes of walking spread over the hours after a meal lowered the
 * insulin area by 23% at light and at moderate intensity alike (Dunstan et al.
 * 2012). A bout of exercise raises insulin sensitivity for at least 16 hours
 * (Borghouts & Keizer 2000). No study found gives a figure for the size of that, nor
 * for the fall of the level during exercise or for lifestyle as a multiplier: those
 * constants are this model's assumptions, named below, not measurements.
 */
class InsulinCurve
{
    /**
     * Grams of glucose in 1000 kJ, the reference of the glucose score.
     */
    private const GLUCOSE_REFERENCE_GRAMS = 62.5;

    private const KJ_PER_KCAL = 4.184;

    /**
     * The level with nothing eaten. Assumption: fasting insulin is about a tenth of
     * the peak after a meal of glucose.
     */
    public const BASE_LEVEL = 10.0;

    /**
     * Minutes to the peak after 1000 kJ of glucose; it scales a demand to a peak.
     */
    private const REFERENCE_PEAK_MINUTES = 40.0;

    private const FASTEST_PEAK_MINUTES = 30.0;

    private const SLOWEST_PEAK_MINUTES = 90.0;

    /**
     * Levels above which the day counts as medium and as high.
     */
    public const MEDIUM_LEVEL = 30.0;

    public const HIGH_LEVEL = 60.0;

    /**
     * Dunstan et al. 2012: 28 minutes of walking, 23% less insulin. Assumption: it
     * grows with the minutes up to this limit.
     */
    private const WALK_REDUCTION = 0.23;

    private const WALK_MINUTES = 28.0;

    private const MAX_WALK_REDUCTION = 0.35;

    /**
     * Assumption: a hard hour (about 400 MET-minutes above rest) lowers the next
     * meals by this share at most, fading over the day.
     */
    private const MAX_SENSITIVITY_GAIN = 0.15;

    private const FULL_GAIN_MET_MINUTES = 400.0;

    private const GAIN_LASTS_MINUTES = 24 * 60;

    /**
     * Assumption: the level falls while the body moves, by this share per MET
     * above rest, at most by the limit.
     */
    private const FALL_PER_MET = 0.03;

    private const MAX_FALL = 0.25;

    /**
     * Assumption: how much more or less insulin the same food takes by the way of
     * life, from sitting to very active; the levels are those of the calculator.
     */
    public const LIFESTYLE_FACTORS = [1 => 1.15, 2 => 1.05, 3 => 1.0, 4 => 0.9, 5 => 0.8];

    public const DEFAULT_LIFESTYLE = 1;

    /**
     * The insulin demand of a portion, by the regression above; 100 is 1000 kJ of
     * glucose. A food without a known GI counts as a medium one.
     *
     * @param  array{grams: float, calories: float, proteins: float, fats: float, carbohydrates: float, sugars: float|null, glycemic_index: int|null}  $food  per 100 g, but grams
     */
    public static function load(array $food): float
    {
        $portion = $food['grams'] / 100;
        $carbohydrates = $food['carbohydrates'] * $portion;
        $glycemicIndex = $food['glycemic_index'] ?? 55;
        $sugars = ($food['sugars'] ?? 0) * $portion;
        $kilojoules = $food['calories'] * $portion * self::KJ_PER_KCAL;

        $load = 0.9 * ($glycemicIndex * $carbohydrates / self::GLUCOSE_REFERENCE_GRAMS)
            + 0.3 * $sugars
            + 0.5 * $food['proteins'] * $portion
            + 0.4 * $food['fats'] * $portion
            - 4.2 * $kilojoules / 1000;

        return max(0.0, round($load, 2));
    }

    /**
     * Minutes from eating to the peak: earlier for a fast food, later for a slow or
     * a fatty one.
     *
     * @param  array{grams: float, fats: float, glycemic_index: int|null}  $food
     */
    public static function peakMinutes(array $food): float
    {
        $byIndex = self::REFERENCE_PEAK_MINUTES + (70 - ($food['glycemic_index'] ?? 55)) * 0.6;
        $byFat = min(20.0, $food['fats'] * $food['grams'] / 100);

        return max(self::FASTEST_PEAK_MINUTES, min(self::SLOWEST_PEAK_MINUTES, $byIndex + $byFat));
    }

    /**
     * The level at each moment of the span.
     *
     * @param  array<int, array{minute: int, load: float, peak_minutes: float}>  $foods  minute counted from the start of the span
     * @param  array<int, array{minute: int, minutes: int, met: float}>  $activities
     * @param  array<int, int>  $moments
     * @return array<int, float> per moment
     */
    public static function levels(array $foods, array $activities, int $lifestyle, array $moments): array
    {
        $factor = self::LIFESTYLE_FACTORS[$lifestyle] ?? self::LIFESTYLE_FACTORS[self::DEFAULT_LIFESTYLE];
        $levels = [];

        foreach ($moments as $moment) {
            $rise = 0.0;

            foreach ($foods as $food) {
                $since = $moment - $food['minute'];
                if ($since <= 0) {
                    continue;
                }

                $rise += self::rise($food['load'], $food['peak_minutes'], $since)
                    * (1 - self::reductionAfterMeal($food['minute'], $moment, $activities))
                    * (1 - self::sensitivityGain($food['minute'], $activities));
            }

            $levels[$moment] = round((self::BASE_LEVEL + $rise * $factor) * (1 - self::fallWhileMoving($moment, $activities)), 1);
        }

        return $levels;
    }

    /**
     * How high one food lifts the level above base at its peak, as a moderately
     * active person: the demand scaled so that a faster food peaks higher.
     */
    public static function peakRise(float $load, float $peakMinutes): float
    {
        return $load * self::REFERENCE_PEAK_MINUTES / $peakMinutes;
    }

    private static function rise(float $load, float $peakMinutes, float $since): float
    {
        // A slow start and a tail nearly gone by four times the peak minutes.
        $phase = $since / $peakMinutes;

        return self::peakRise($load, $peakMinutes) * $phase * $phase * exp(2 * (1 - $phase));
    }

    /**
     * Movement since the meal lowers what the meal still does.
     *
     * @param  array<int, array{minute: int, minutes: int, met: float}>  $activities
     */
    private static function reductionAfterMeal(int $mealMinute, int $moment, array $activities): float
    {
        $moved = 0.0;

        foreach ($activities as $activity) {
            $start = max($activity['minute'], $mealMinute);
            $end = min($activity['minute'] + $activity['minutes'], $moment);
            // Sitting in a car does not count: only what is above a slow stroll's light effort.
            if ($end > $start && $activity['met'] >= 2.0) {
                $moved += $end - $start;
            }
        }

        return min(self::MAX_WALK_REDUCTION, self::WALK_REDUCTION * $moved / self::WALK_MINUTES);
    }

    /**
     * Movement before the meal leaves the body more sensitive for the rest of the day.
     *
     * @param  array<int, array{minute: int, minutes: int, met: float}>  $activities
     */
    private static function sensitivityGain(int $mealMinute, array $activities): float
    {
        $gain = 0.0;

        foreach ($activities as $activity) {
            $since = $mealMinute - ($activity['minute'] + $activity['minutes']);
            if ($since < 0 || $since > self::GAIN_LASTS_MINUTES) {
                continue;
            }

            $effort = max(0.0, $activity['met'] - 1) * $activity['minutes'] / self::FULL_GAIN_MET_MINUTES;
            $gain += self::MAX_SENSITIVITY_GAIN * min(1.0, $effort) * (1 - $since / self::GAIN_LASTS_MINUTES);
        }

        return min(self::MAX_SENSITIVITY_GAIN, $gain);
    }

    /**
     * @param  array<int, array{minute: int, minutes: int, met: float}>  $activities
     */
    private static function fallWhileMoving(int $moment, array $activities): float
    {
        $fall = 0.0;

        foreach ($activities as $activity) {
            if ($moment >= $activity['minute'] && $moment < $activity['minute'] + $activity['minutes']) {
                $fall = max($fall, min(self::MAX_FALL, self::FALL_PER_MET * max(0.0, $activity['met'] - 1)));
            }
        }

        return $fall;
    }
}
