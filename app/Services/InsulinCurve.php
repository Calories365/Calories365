<?php

namespace App\Services;

/**
 * An estimate of how blood sugar and insulin move over a day, from what was
 * eaten and how the body moved and slept. Nothing is measured: sugar is in
 * mmol/L of a healthy adult, insulin a relative index in which 100 is the peak
 * after 1000 kJ of glucose (about 62 g) in a moderately active person.
 *
 * Sugar. The rise after a meal follows its glycemic load, GI × available
 * carbohydrates / 100: stepped loads gave stepped rises (Brand-Miller et al.
 * 2003), and across composite meals the load explained 58% of the glucose
 * response, carbohydrates alone did not (Bao et al. 2011). Above 50 g the rise
 * grows slower: 50 g more of carbohydrate from bread gave 65% more, not 100%
 * (Meng et al. 2017). The same meal lifts sugar less with protein: 50 g of
 * protein took 25% off (Meng et al. 2017); fat does it about a third as much per
 * gram (Moghaddam et al. 2006) and no significant change was seen up to 22 g in
 * a solid meal. Fibre: viscous oat beta-glucan takes off about 23% (meta-analysis
 * of 2021), 9.6 g of added fibre changed nothing significant (Meng 2017); the
 * fibre of the vegetables of a meal counts here a little, 0.8% a gram, at most 15%,
 * an assumption between those. The fibre of starchy foods is already in their GI.
 * The same meal at 8 pm lifts sugar 17% more than at 8 am (Morris et al. 2015).
 * After a fast meal sugar dips below the fasting level 2 to 3 hours later, and
 * the dips predict hunger and the next meal (Wyatt et al. 2021); their size here
 * is an assumption.
 *
 * Insulin. What a food demands comes from its food insulin index, when the
 * product model gave it (ProductQuality, the white-bread scale of Holt et al.
 * 1997, calibrated to this curve by BREAD_TO_GLUCOSE), otherwise from the
 * regression of Bell et al. 2016 (Nutrients 8, 210) on 147 foods:
 *
 *     FII = -4.2 + 0.9 glucose score + 0.3 sugar + 0.5 protein + 0.4 fat
 *
 * per 1000 kJ; the indexes of the foods of a meal add up (Bao et al. 2009).
 *
 * Moving. Walking after a meal, 28 minutes spread over five hours, lowered
 * glucose by 24% at light and 30% at moderate intensity and insulin by 23%
 * (Dunstan et al. 2012); ten minutes after each meal lowered sugar by 12%, after
 * dinner by 22% (Reynolds et al. 2016). A bout of exercise raises insulin
 * sensitivity for a day or two, gone by about 48 hours (Hawley & Lessard 2008). One night of four
 * hours of sleep lowered insulin sensitivity by about 25% (Donga et al. 2010).
 * The size of the lasting effect of exercise, the fall of insulin while moving
 * and the way of life as a multiplier are this model's assumptions, named below.
 */
class InsulinCurve
{
    /**
     * Fasting blood sugar of a healthy adult, mmol/L (assumption, within 4.0–5.4).
     */
    public const GLUCOSE_BASE = 5.0;

    /**
     * Above it healthy people spend about 30 minutes a day (Shah et al. 2019).
     */
    public const GLUCOSE_HIGH = 7.8;

    public const GLUCOSE_VERY_HIGH = 10.0;

    /**
     * The insulin level with nothing eaten. Assumption: fasting insulin is about
     * a tenth of the peak after a meal of glucose.
     */
    public const BASE_LEVEL = 10.0;

    /**
     * Insulin levels above which the day counts as medium and as high.
     */
    public const MEDIUM_LEVEL = 30.0;

    public const HIGH_LEVEL = 60.0;

    /**
     * Assumption: how much more or less the same food lifts by the way of life,
     * from sitting to very active; the levels are those of the calculator.
     */
    public const LIFESTYLE_FACTORS = [1 => 1.15, 2 => 1.05, 3 => 1.0, 4 => 0.9, 5 => 0.8];

    public const DEFAULT_LIFESTYLE = 1;

    /**
     * 50 g of glucose, a load of 50, lifts sugar by about 3 mmol/L at about 30
     * minutes in a young healthy adult.
     */
    private const REFERENCE_LOAD = 50.0;

    private const REFERENCE_RISE = 3.2;

    private const REFERENCE_GLUCOSE_PEAK_MINUTES = 30.0;

    private const LARGE_LOAD_EXPONENT = 0.72;

    /**
     * Minutes to the peak of sugar, by the GI of the meal and slower with fat.
     */
    private const FASTEST_PEAK_MINUTES = 30.0;

    private const SLOWEST_PEAK_MINUTES = 90.0;

    /**
     * Insulin peaks a little after sugar; 1000 kJ of glucose peaks at 100 above
     * base at 40 minutes, which scales a demand to a peak.
     */
    private const INSULIN_LAG_MINUTES = 5.0;

    private const REFERENCE_INSULIN_PEAK_MINUTES = 40.0;

    private const PROTEIN_LOWERS = 0.0067;

    private const FAT_LOWERS = 0.0022;

    private const FIBRE_LOWERS = 0.008;

    private const MAX_FIBRE_LOWERS = 0.15;

    /**
     * A food with fewer available carbohydrates per 100 g counts its fibre as the
     * vegetables of the meal.
     */
    private const VEGETABLE_CARBOHYDRATES = 10.0;

    private const EVENING_RISE = 0.17;

    /**
     * Assumption: the dip of a fast meal is at most this share of its rise.
     */
    private const DIP_SHARE = 0.2;

    private const GLUCOSE_FLOOR = 3.6;

    private const GLUCOSE_REFERENCE_GRAMS = 62.5;

    private const KJ_PER_KCAL = 4.184;

    /**
     * The regression gives white bread half of what the white-bread scale does:
     * the least-squares slope over 29 foods of Holt et al. 1997 was 0.49.
     */
    private const BREAD_TO_GLUCOSE = 0.49;

    /**
     * Walking after a meal (Dunstan et al. 2012): 28 minutes, these shares off.
     * Only movement from a light walk up counts; the caps are assumptions.
     */
    private const WALK_MINUTES = 28.0;

    private const WALK_LOWERS_GLUCOSE = 0.24;

    private const BRISK_WALK_LOWERS_GLUCOSE = 0.30;

    private const WALK_LOWERS_INSULIN = 0.23;

    private const MAX_WALK_LOWERS_GLUCOSE = 0.40;

    private const MAX_WALK_LOWERS_INSULIN = 0.35;

    private const WALK_MET = 2.5;

    private const MODERATE_MET = 3.0;

    /**
     * Movement this long after a meal still counts for it.
     */
    private const AFTER_MEAL_MINUTES = 180;

    /**
     * Assumption: a hard hour (about 400 MET-minutes above rest) lowers the
     * next meals by this share at most, fading over two days.
     */
    private const MAX_SENSITIVITY_GAIN = 0.15;

    private const FULL_GAIN_MET_MINUTES = 400.0;

    private const GAIN_LASTS_MINUTES = 48 * 60;

    /**
     * Assumption: insulin falls while the body moves, by this share per MET
     * above rest, at most by the limit.
     */
    private const FALL_PER_MET = 0.03;

    private const MAX_FALL = 0.25;

    /**
     * Donga et al. 2010: four hours of sleep, 25% less sensitive; seven and more
     * change nothing, between them it grows evenly (assumption). Sleep shorter
     * than a nap's limit is a nap, not the night.
     */
    private const SHORT_SLEEP_RISE = 0.25;

    private const ENOUGH_SLEEP_HOURS = 7.0;

    private const SHORT_SLEEP_HOURS = 4.0;

    private const NIGHT_MIN_MINUTES = 180;

    private const SLEEP_COUNTS_MINUTES = 18 * 60;

    /**
     * The glycemic load of a portion; a food without a known GI counts as a
     * medium one.
     *
     * @param  array{grams: float, carbohydrates: float, glycemic_index?: int|null}  $food  per 100 g, but grams
     */
    public static function glycemicLoad(array $food): float
    {
        return ($food['glycemic_index'] ?? 55) * $food['carbohydrates'] * $food['grams'] / 10000;
    }

    /**
     * The insulin demand of a portion; 100 is 1000 kJ of glucose.
     *
     * @param  array{grams: float, calories: float, proteins: float, fats: float, carbohydrates: float, sugars?: float|null, glycemic_index?: int|null, insulin_index?: int|null}  $food  per 100 g, but grams
     */
    public static function load(array $food): float
    {
        $portion = $food['grams'] / 100;
        $kilojoules = $food['calories'] * $portion * self::KJ_PER_KCAL;

        if (($food['insulin_index'] ?? null) !== null) {
            return round($food['insulin_index'] * self::BREAD_TO_GLUCOSE * $kilojoules / 1000, 2);
        }

        $carbohydrates = $food['carbohydrates'] * $portion;
        $load = 0.9 * (($food['glycemic_index'] ?? 55) * $carbohydrates / self::GLUCOSE_REFERENCE_GRAMS)
            + 0.3 * ($food['sugars'] ?? 0) * $portion
            + 0.5 * $food['proteins'] * $portion
            + 0.4 * $food['fats'] * $portion
            - 4.2 * $kilojoules / 1000;

        return max(0.0, round($load, 2));
    }

    /**
     * What a meal does by itself, before the time of day, sleep, movement and
     * the way of life: how high it lifts sugar and how soon, how much insulin it
     * asks for, and how much its protein, fat and vegetables took off sugar.
     *
     * @param  array<int, array{grams: float, calories: float, proteins: float, fats: float, carbohydrates: float, fibers?: float|null, sugars?: float|null, glycemic_index?: int|null, insulin_index?: int|null}>  $foods
     * @return array{glycemic_load: float, glycemic_index: int|null, carbohydrates: float, proteins: float, fats: float, fibers: float, sugars: float, calories: float, insulin_load: float, peak_minutes: float, glucose_rise: float, dip: float, lowered: array{proteins: int, fats: int, fibers: int}}
     */
    public static function meal(array $foods): array
    {
        $total = static fn (string $field) => array_sum(array_map(static fn (array $food) => (float) ($food[$field] ?? 0) * $food['grams'] / 100, $foods));
        $carbohydrates = $total('carbohydrates');
        $proteins = $total('proteins');
        $fats = $total('fats');
        $load = array_sum(array_map(static fn (array $food) => self::glycemicLoad($food), $foods));
        $vegetableFibre = array_sum(array_map(
            static fn (array $food) => $food['carbohydrates'] <= self::VEGETABLE_CARBOHYDRATES ? (float) ($food['fibers'] ?? 0) * $food['grams'] / 100 : 0.0,
            $foods
        ));

        $index = $carbohydrates > 0 ? $load / $carbohydrates * 100 : 0.0;
        $peak = max(self::FASTEST_PEAK_MINUTES, min(self::SLOWEST_PEAK_MINUTES, 40 + (70 - $index) * 0.6 + min(20.0, 0.5 * $fats)));
        $effective = $load <= self::REFERENCE_LOAD ? $load : self::REFERENCE_LOAD * ($load / self::REFERENCE_LOAD) ** self::LARGE_LOAD_EXPONENT;

        $byProtein = 1 / (1 + self::PROTEIN_LOWERS * $proteins);
        $byFat = 1 / (1 + self::FAT_LOWERS * $fats);
        $byFibre = max(1 - self::MAX_FIBRE_LOWERS, 1 / (1 + self::FIBRE_LOWERS * $vegetableFibre));

        return [
            'glycemic_load' => round($load, 1),
            'glycemic_index' => $carbohydrates >= 1 ? (int) round($index) : null,
            'carbohydrates' => round($carbohydrates, 1),
            'proteins' => round($proteins, 1),
            'fats' => round($fats, 1),
            'fibers' => round($total('fibers'), 1),
            'sugars' => round($total('sugars'), 1),
            'calories' => round($total('calories')),
            'insulin_load' => round(array_sum(array_map(static fn (array $food) => self::load($food), $foods)), 2),
            'peak_minutes' => round($peak, 1),
            'glucose_rise' => round(self::REFERENCE_RISE * $effective / self::REFERENCE_LOAD * self::REFERENCE_GLUCOSE_PEAK_MINUTES / $peak * $byProtein * $byFat * $byFibre, 3),
            'dip' => $load >= 10 ? round(self::DIP_SHARE * max(0.0, min(1.0, ($index - 55) / 25)), 3) : 0.0,
            'lowered' => [
                'proteins' => (int) round((1 - $byProtein) * 100),
                'fats' => (int) round((1 - $byFat) * 100),
                'fibers' => (int) round((1 - $byFibre) * 100),
            ],
        ];
    }

    /**
     * Sugar and insulin at each moment of the span.
     *
     * @param  array<int, array{minute: int, glucose_rise: float, insulin_load: float, peak_minutes: float, dip: float}>  $meals  minute counted from the start of the span
     * @param  array<int, array{minute: int, minutes: int, met: float, type?: string|null}>  $activities
     * @param  array<int, int>  $moments
     * @return array{glucose: array<int, float>, insulin: array<int, float>}
     */
    public static function levels(array $meals, array $activities, int $lifestyle, array $moments): array
    {
        $contexts = array_map(static fn (array $meal) => self::context($meal['minute'], $activities, $lifestyle), $meals);
        $glucose = [];
        $insulin = [];

        foreach ($moments as $moment) {
            $sugar = 0.0;
            $demand = 0.0;

            foreach ($meals as $index => $meal) {
                $since = $moment - $meal['minute'];
                if ($since <= 0) {
                    continue;
                }

                $peak = $meal['peak_minutes'];
                $insulinPeak = $peak + self::INSULIN_LAG_MINUTES;
                $dip = $meal['dip'] * exp(-((($since - 4 * $peak) / $peak) ** 2));

                $sugar += $meal['glucose_rise'] * $contexts[$index]['glucose']
                    * (1 - self::walkLowers($meal['minute'], $moment, $activities, true))
                    * (self::shape($since / $peak) - $dip);
                $demand += $meal['insulin_load'] * self::REFERENCE_INSULIN_PEAK_MINUTES / $insulinPeak * $contexts[$index]['insulin']
                    * (1 - self::walkLowers($meal['minute'], $moment, $activities, false))
                    * self::shape($since / $insulinPeak);
            }

            $glucose[$moment] = round(max(self::GLUCOSE_FLOOR, self::GLUCOSE_BASE + $sugar), 2);
            $insulin[$moment] = round((self::BASE_LEVEL + $demand) * (1 - self::fallWhileMoving($moment, $activities)), 1);
        }

        return ['glucose' => $glucose, 'insulin' => $insulin];
    }

    /**
     * What around a meal changes what it does: the way of life, the night before,
     * exercise before it and, for sugar, the hour; each as a multiplier.
     *
     * @param  array<int, array{minute: int, minutes: int, met: float, type?: string|null}>  $activities
     * @return array{glucose: float, insulin: float, lifestyle: float, sleep: float, exercise: float, evening: float}
     */
    public static function context(int $minute, array $activities, int $lifestyle): array
    {
        $byLifestyle = self::LIFESTYLE_FACTORS[$lifestyle] ?? self::LIFESTYLE_FACTORS[self::DEFAULT_LIFESTYLE];
        $bySleep = self::sleepFactor($minute, $activities);
        $byExercise = 1 - self::sensitivityGain($minute, $activities);
        $byEvening = 1 + self::EVENING_RISE * self::evening($minute);

        return [
            'glucose' => $byLifestyle * $bySleep * $byExercise * $byEvening,
            'insulin' => $byLifestyle * $bySleep * $byExercise,
            'lifestyle' => $byLifestyle,
            'sleep' => $bySleep,
            'exercise' => $byExercise,
            'evening' => $byEvening,
        ];
    }

    /**
     * A slow start, the peak at 1, and a tail nearly gone by 4.
     */
    private static function shape(float $phase): float
    {
        return $phase * $phase * exp(2 * (1 - $phase));
    }

    /**
     * How far into the evening a minute of the day is: 0 from 4 to 8 in the
     * morning, rising to 1 by 8 in the evening and staying until 4.
     */
    private static function evening(int $minute): float
    {
        $inDay = (($minute % 1440) + 1440) % 1440;

        return match (true) {
            $inDay < 240, $inDay >= 1200 => 1.0,
            $inDay < 480 => 0.0,
            default => (1 - cos(M_PI * ($inDay - 480) / 720)) / 2,
        };
    }

    /**
     * Walking since the meal lowers what the meal still does.
     *
     * @param  array<int, array{minute: int, minutes: int, met: float, type?: string|null}>  $activities
     */
    private static function walkLowers(int $mealMinute, int $moment, array $activities, bool $glucose): float
    {
        $lowered = 0.0;

        foreach ($activities as $activity) {
            if ($activity['met'] < self::WALK_MET) {
                continue;
            }

            $start = max($activity['minute'], $mealMinute);
            $end = min($activity['minute'] + $activity['minutes'], $moment, $mealMinute + self::AFTER_MEAL_MINUTES);
            if ($end <= $start) {
                continue;
            }

            $share = $glucose
                ? ($activity['met'] >= self::MODERATE_MET ? self::BRISK_WALK_LOWERS_GLUCOSE : self::WALK_LOWERS_GLUCOSE)
                : self::WALK_LOWERS_INSULIN;
            $lowered += $share * ($end - $start) / self::WALK_MINUTES;
        }

        return min($glucose ? self::MAX_WALK_LOWERS_GLUCOSE : self::MAX_WALK_LOWERS_INSULIN, $lowered);
    }

    /**
     * Exercise before the meal leaves the body more sensitive for two days.
     *
     * @param  array<int, array{minute: int, minutes: int, met: float, type?: string|null}>  $activities
     */
    private static function sensitivityGain(int $mealMinute, array $activities): float
    {
        $gain = 0.0;

        foreach ($activities as $activity) {
            $since = $mealMinute - ($activity['minute'] + $activity['minutes']);
            if ($activity['met'] < self::MODERATE_MET || $since < 0 || $since > self::GAIN_LASTS_MINUTES) {
                continue;
            }

            $effort = ($activity['met'] - 1) * $activity['minutes'] / self::FULL_GAIN_MET_MINUTES;
            $gain += self::MAX_SENSITIVITY_GAIN * min(1.0, $effort) * (1 - $since / self::GAIN_LASTS_MINUTES);
        }

        return min(self::MAX_SENSITIVITY_GAIN, $gain);
    }

    /**
     * After a short night the same meal lifts more: the longest sleep that ended
     * within 18 hours before the meal is the night.
     *
     * @param  array<int, array{minute: int, minutes: int, met: float, type?: string|null}>  $activities
     */
    private static function sleepFactor(int $mealMinute, array $activities): float
    {
        $night = 0;

        foreach ($activities as $activity) {
            $ended = $activity['minute'] + $activity['minutes'];
            if (($activity['type'] ?? null) === ActivityCatalog::SLEEP
                && $activity['minutes'] >= self::NIGHT_MIN_MINUTES
                && $ended <= $mealMinute
                && $ended >= $mealMinute - self::SLEEP_COUNTS_MINUTES) {
                $night = max($night, $activity['minutes']);
            }
        }

        if ($night === 0) {
            return 1.0;
        }

        $short = (self::ENOUGH_SLEEP_HOURS - $night / 60) / (self::ENOUGH_SLEEP_HOURS - self::SHORT_SLEEP_HOURS);

        return 1 + self::SHORT_SLEEP_RISE * max(0.0, min(1.0, $short));
    }

    /**
     * @param  array<int, array{minute: int, minutes: int, met: float, type?: string|null}>  $activities
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
