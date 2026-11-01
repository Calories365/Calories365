<?php

namespace App\Services;

/**
 * An estimate of how blood sugar and insulin move over a day, from what was
 * eaten and how the body moved and slept. Nothing is measured: sugar is in
 * mmol/L of a healthy adult, insulin a relative index in which 100 is the peak
 * after 1000 kJ of glucose (about 62 g) in a moderately active person.
 *
 * Sugar. The rise after a meal follows its glycemic load, GI × available
 * carbohydrates / 100 (Brand-Miller et al. 2003; Bao et al. 2011), and grows
 * slower than the load: 25 to 50 g of glucose gave 68% more, 50 to 100 g 38% more
 * (Lee & Wolever 1998). How high and how long is fitted to continuous sugar of
 * healthy adults (Freckmann et al. 2007, 2024): 75 g of glucose +3.75 mmol/L at 30
 * to 60 minutes, back by three hours; ordinary meals +2.6 at about 50 minutes,
 * then a low tail for three to four hours, longer after more carbohydrates and
 * slow ones. The same meal lifts sugar less with protein: 50 g of protein took
 * 25% off (Meng et al. 2017); fat does it about a third as much per gram
 * (Moghaddam et al. 2006). Fibre: viscous oat beta-glucan takes off about 23%
 * (meta-analysis of 2021), 9.6 g of added fibre changed nothing significant (Meng
 * 2017); the fibre of the vegetables of a meal counts here a little, 0.8% a gram,
 * at most 15%, an assumption between those. The fibre of starchy foods is already
 * in their GI, and still counts for the fibre of the day. The same meal at 8 pm
 * lifts sugar 17% more than at 8 am (Morris et al. 2015). After a fast meal sugar
 * dips below the fasting level some hours later (Freckmann 2007), and the dips
 * predict hunger (Wyatt et al. 2021); their size here is an assumption.
 *
 * Insulin. What a food demands comes from its food insulin index, when the
 * product model gave it (ProductQuality, the white-bread scale of Holt et al.
 * 1997, calibrated to this curve by BREAD_TO_GLUCOSE), otherwise from the
 * regression of Bell et al. 2016 (Nutrients 8, 210) on 147 foods:
 *
 *     FII = -4.2 + 0.9 glucose score + 0.3 sugar + 0.5 protein + 0.4 fat
 *
 * per 1000 kJ; the indexes of the foods of a meal add up (Bao et al. 2009). The
 * terms of that regression split the demand in two: the part of carbohydrates
 * follows sugar, and the part of protein and fat is drawn apart and comes slower,
 * since protein raises insulin along with glucagon and sugar stays where it is
 * (Nuttall & Gannon 2011). Only the first part tells a zone.
 *
 * Moving. Walking begun after a meal lowers what is left of it (Engeroff et al.
 * 2023). Dunstan et al. 2012 saw 24% at light and 30% at moderate intensity and
 * 23% of insulin from 28 minutes in two-minute breaks over five hours, in
 * overweight adults; one walk does less than such breaks (Peddie et al. 2013) and
 * this estimate is for a healthy adult, so half of it counts (assumption). Before
 * a meal movement hardly changes the sugar of that meal (Engeroff 2023; Peddie
 * 2013) and it does raise insulin sensitivity for a day or two (Newsom et al.
 * 2013; Hawley & Lessard 2008), by its effort. One night of four hours of sleep
 * lowered insulin sensitivity by about 25% (Donga et al. 2010). The size of the
 * lasting effect, the fall of insulin while moving and the way of life as a
 * multiplier are this model's assumptions, named below.
 */
class InsulinCurve
{
    /**
     * Fasting blood sugar of a healthy adult, mmol/L (assumption, within 4.0–5.4).
     */
    public const GLUCOSE_BASE = 5.0;

    /**
     * Above it healthy people spend 30 to 70 minutes a day (Shah et al. 2019;
     * Freckmann et al. 2024).
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
     * How high a load lifts sugar: MAX_RISE × load / (load + HALF_LOAD), so that 75
     * g of glucose gives +3.75 mmol/L; a slower meal of the same load less, a
     * faster one more, by the square root of its GI to the middle, up to the limit
     * (fitted to the meals of Freckmann et al. 2007, 2024; their GIs are estimated).
     */
    private const MAX_RISE = 5.33;

    private const HALF_LOAD = 40.0;

    private const MIDDLE_INDEX = 60.0;

    private const FASTEST_INDEX = 70.0;

    /**
     * Minutes to the peak of sugar, by the GI of the meal and slower with fat;
     * healthy adults peak at 43 to 59 minutes after any meal (Freckmann 2007).
     */
    private const FASTEST_PEAK_MINUTES = 40.0;

    private const SLOWEST_PEAK_MINUTES = 60.0;

    /**
     * Food starts to lift sugar a few minutes after the first bite; a sharp peak
     * on top of a low hump: its height, a share of the peak, grows with more
     * carbohydrates than 50 g and with a GI under 60; it is over sooner after a
     * fast meal. Fitted to the same curves.
     */
    private const ONSET_MINUTES = 5.0;

    private const FAST_SHAPE = 3.5;

    private const SLOW_SHAPE = 3.0;

    private const SLOW_SHARE = 0.09;

    private const SLOW_SHARE_BY_CARBOHYDRATES = 0.62;

    private const SLOW_SHARE_BY_INDEX = 0.46;

    private const MIN_SLOW_SHARE = 0.05;

    private const MAX_SLOW_SHARE = 0.35;

    private const SLOW_PEAK_MINUTES = 115.0;

    private const SLOW_PEAK_MINUTES_OF_FAST_MEAL = 90.0;

    /**
     * Insulin peaks a little after sugar; what protein and fat ask for comes later
     * still and is gone by about four hours (assumptions). 1000 kJ of glucose
     * peaks at 100 above base, which scales a demand to a peak: the indexes tell
     * the area of insulin, so a longer curve is a lower one.
     */
    private const INSULIN_LAG_MINUTES = 5.0;

    private const PROTEIN_INSULIN_LAG_MINUTES = 15.0;

    private const PROTEIN_INSULIN_SHAPE = 3.0;

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
     * Assumption: the dip of a fast meal is at most this share of its rise, deepest
     * at five times the minutes to its peak: 75 g of glucose is below the fasting
     * level at three hours, a toast with honey from three to five (Freckmann 2007).
     */
    private const DIP_SHARE = 0.2;

    private const DIP_AT_PEAKS = 5.0;

    private const GLUCOSE_FLOOR = 3.6;

    private const GLUCOSE_REFERENCE_GRAMS = 62.5;

    private const KJ_PER_KCAL = 4.184;

    /**
     * The regression gives white bread half of what the white-bread scale does:
     * the least-squares slope over 29 foods of Holt et al. 1997 was 0.49.
     */
    private const BREAD_TO_GLUCOSE = 0.49;

    /**
     * Walking begun after a meal: half of what 28 minutes did in Dunstan et al.
     * 2012 (see above). Only movement from a light walk up counts; the caps are
     * assumptions.
     */
    private const WALK_MINUTES = 28.0;

    private const WALK_LOWERS_GLUCOSE = 0.12;

    private const BRISK_WALK_LOWERS_GLUCOSE = 0.15;

    private const WALK_LOWERS_INSULIN = 0.115;

    private const MAX_WALK_LOWERS_GLUCOSE = 0.25;

    private const MAX_WALK_LOWERS_INSULIN = 0.20;

    private const WALK_MET = 2.5;

    private const MODERATE_MET = 3.0;

    /**
     * Movement begun this long after a meal still counts as after it.
     */
    public const AFTER_MEAL_MINUTES = 180;

    /**
     * Movement before a meal, from a light walk up. 350 kcal at half of the peak
     * oxygen uptake, about 70 minutes, gave 35% more insulin sensitivity the next
     * day in obese adults (Newsom et al. 2013). Assumption for a healthy adult:
     * insulin of the next meals at most 20% lower, reached at 300 MET-minutes above
     * rest, sugar a quarter of that, fading over two days.
     */
    private const MAX_SENSITIVITY_GAIN = 0.20;

    private const GLUCOSE_SHARE_OF_GAIN = 0.25;

    private const FULL_GAIN_MET_MINUTES = 300.0;

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
     * The share of a food's insulin demand that comes from its carbohydrates, by
     * the terms of the regression of Bell et al. 2016; the rest is of protein and fat.
     *
     * @param  array{grams: float, proteins: float, fats: float, carbohydrates: float, sugars?: float|null, glycemic_index?: int|null}  $food  per 100 g, but grams
     */
    public static function carbohydrateShare(array $food): float
    {
        $portion = $food['grams'] / 100;
        $carbohydrates = 0.9 * (($food['glycemic_index'] ?? 55) * $food['carbohydrates'] * $portion / self::GLUCOSE_REFERENCE_GRAMS)
            + 0.3 * ($food['sugars'] ?? 0) * $portion;
        $others = (0.5 * $food['proteins'] + 0.4 * $food['fats']) * $portion;

        return $carbohydrates + $others > 0 ? $carbohydrates / ($carbohydrates + $others) : 0.0;
    }

    /**
     * What a meal does by itself, before the time of day, sleep, movement and
     * the way of life: how high it lifts sugar and how soon, how much insulin its
     * carbohydrates and its protein and fat ask for, and how much its protein, fat
     * and vegetables took off sugar.
     *
     * @param  array<int, array{grams: float, calories: float, proteins: float, fats: float, carbohydrates: float, fibers?: float|null, sugars?: float|null, glycemic_index?: int|null, insulin_index?: int|null}>  $foods
     * @return array{glycemic_load: float, glycemic_index: int|null, carbohydrates: float, proteins: float, fats: float, fibers: float, sugars: float, calories: float, insulin_load: float, insulin_load_of_carbohydrates: float, peak_minutes: float, slow: float, slow_peak: float, glucose_height: float, insulin_height: float, glucose_rise: float, insulin_of_carbohydrates: float, insulin_of_protein: float, dip: float, lowered: array{proteins: int, fats: int, fibers: int}}
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
        $peak = max(self::FASTEST_PEAK_MINUTES, min(self::SLOWEST_PEAK_MINUTES, 40 + (65 - $index) * 0.3 + min(10.0, 0.15 * $fats)));
        [$slow, $slowPeak] = self::slowHump($carbohydrates, $index);

        $byProtein = 1 / (1 + self::PROTEIN_LOWERS * $proteins);
        $byFat = 1 / (1 + self::FAT_LOWERS * $fats);
        $byFibre = max(1 - self::MAX_FIBRE_LOWERS, 1 / (1 + self::FIBRE_LOWERS * $vegetableFibre));
        $bySpeed = sqrt(min(self::FASTEST_INDEX, $index) / self::MIDDLE_INDEX);

        $insulinLoad = array_sum(array_map(static fn (array $food) => self::load($food), $foods));
        $carbohydrateLoad = array_sum(array_map(static fn (array $food) => self::load($food) * self::carbohydrateShare($food), $foods));
        $insulinPeak = $peak + self::INSULIN_LAG_MINUTES;

        return [
            'glycemic_load' => round($load, 1),
            'glycemic_index' => $carbohydrates >= 1 ? (int) round($index) : null,
            'carbohydrates' => round($carbohydrates, 1),
            'proteins' => round($proteins, 1),
            'fats' => round($fats, 1),
            'fibers' => round($total('fibers'), 1),
            'sugars' => round($total('sugars'), 1),
            'calories' => round($total('calories')),
            'insulin_load' => round($insulinLoad, 2),
            'insulin_load_of_carbohydrates' => round($carbohydrateLoad, 2),
            'peak_minutes' => round($peak, 1),
            'slow' => round($slow, 3),
            'slow_peak' => round($slowPeak, 1),
            'glucose_height' => self::height($peak, $slow, $slowPeak),
            'insulin_height' => self::height($insulinPeak, $slow, $slowPeak),
            'glucose_rise' => round(self::MAX_RISE * $load / ($load + self::HALF_LOAD) * $bySpeed * $byProtein * $byFat * $byFibre, 3),
            'insulin_of_carbohydrates' => round($carbohydrateLoad * self::referenceArea() / self::area($insulinPeak, $slow, $slowPeak), 2),
            'insulin_of_protein' => round(($insulinLoad - $carbohydrateLoad) * self::referenceArea() / self::proteinArea($peak), 2),
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
     * @param  array<int, array{minute: int, glucose_rise: float, peak_minutes: float, slow: float, slow_peak: float, glucose_height: float, insulin_height: float, insulin_of_carbohydrates: float, insulin_of_protein: float, dip: float}>  $meals  minute counted from the start of the span
     * @param  array<int, array{minute: int, minutes: int, met: float, type?: string|null}>  $activities
     * @param  array<int, int>  $moments
     * @return array{glucose: array<int, float>, insulin: array<int, float>, insulin_of_carbohydrates: array<int, float>}
     */
    public static function levels(array $meals, array $activities, int $lifestyle, array $moments): array
    {
        $contexts = array_map(static fn (array $meal) => self::context($meal['minute'], $activities, $lifestyle), $meals);
        $glucose = [];
        $insulin = [];
        $ofCarbohydrates = [];

        foreach ($moments as $moment) {
            $sugar = 0.0;
            $demand = 0.0;
            $proteinDemand = 0.0;

            foreach ($meals as $index => $meal) {
                $since = $moment - $meal['minute'];
                if ($since <= 0) {
                    continue;
                }

                $peak = $meal['peak_minutes'];
                $insulinPeak = $peak + self::INSULIN_LAG_MINUTES;
                $dip = $meal['dip'] * exp(-((($since - self::DIP_AT_PEAKS * $peak) / $peak) ** 2));
                $lowered = 1 - self::walkLowers($meal['minute'], $moment, $activities, false);

                $sugar += $meal['glucose_rise'] * $contexts[$index]['glucose']
                    * (1 - self::walkLowers($meal['minute'], $moment, $activities, true))
                    * (self::curve($since, $peak, $meal['slow'], $meal['slow_peak']) / $meal['glucose_height'] - $dip);
                $demand += $meal['insulin_of_carbohydrates'] * $contexts[$index]['insulin'] * $lowered
                    * self::curve($since, $insulinPeak, $meal['slow'], $meal['slow_peak']) / $meal['insulin_height'];
                $proteinDemand += $meal['insulin_of_protein'] * $contexts[$index]['insulin'] * $lowered
                    * self::proteinCurve($since, $peak);
            }

            $moving = 1 - self::fallWhileMoving($moment, $activities);
            $glucose[$moment] = round(max(self::GLUCOSE_FLOOR, self::GLUCOSE_BASE + $sugar), 2);
            $ofCarbohydrates[$moment] = round((self::BASE_LEVEL + $demand) * $moving, 1);
            $insulin[$moment] = round((self::BASE_LEVEL + $demand + $proteinDemand) * $moving, 1);
        }

        return ['glucose' => $glucose, 'insulin' => $insulin, 'insulin_of_carbohydrates' => $ofCarbohydrates];
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
        $gain = self::sensitivityGain($minute, $activities);
        $byExercise = 1 - $gain;
        $byEvening = 1 + self::EVENING_RISE * self::evening($minute);

        return [
            'glucose' => $byLifestyle * $bySleep * (1 - self::GLUCOSE_SHARE_OF_GAIN * $gain) * $byEvening,
            'insulin' => $byLifestyle * $bySleep * $byExercise,
            'lifestyle' => $byLifestyle,
            'sleep' => $bySleep,
            'exercise' => $byExercise,
            'evening' => $byEvening,
        ];
    }

    /**
     * A curve with its peak of 1 at phase 1, sharper the higher the power.
     */
    private static function shape(float $phase, float $power): float
    {
        return $phase <= 0 ? 0.0 : $phase ** $power * exp($power * (1 - $phase));
    }

    /**
     * The sharp peak of a meal with the low hump after it.
     */
    private static function curve(float $since, float $peak, float $slow, float $slowPeak): float
    {
        return self::shape(($since - self::ONSET_MINUTES) / ($peak - self::ONSET_MINUTES), self::FAST_SHAPE)
            + $slow * self::shape($since / $slowPeak, self::SLOW_SHAPE);
    }

    private static function proteinCurve(float $since, float $peak): float
    {
        return self::shape($since / ($peak + self::PROTEIN_INSULIN_LAG_MINUTES), self::PROTEIN_INSULIN_SHAPE);
    }

    /**
     * How high and how long the low hump of a meal is: more than 50 g of
     * carbohydrates and a GI under 60 raise it.
     *
     * @return array{0: float, 1: float} its height, the minutes to its peak
     */
    private static function slowHump(float $carbohydrates, float $index): array
    {
        $slow = self::SLOW_SHARE
            + self::SLOW_SHARE_BY_CARBOHYDRATES * max(0.0, min(1.0, ($carbohydrates - 50) / 50))
            + self::SLOW_SHARE_BY_INDEX * max(0.0, min(1.0, (60 - $index) / 30));
        $fast = max(0.0, min(1.0, ($index - 55) / 20));

        return [
            max(self::MIN_SLOW_SHARE, min(self::MAX_SLOW_SHARE, $slow)),
            self::SLOW_PEAK_MINUTES + (self::SLOW_PEAK_MINUTES_OF_FAST_MEAL - self::SLOW_PEAK_MINUTES) * $fast,
        ];
    }

    /**
     * The highest point of a curve, to bring its peak to 1.
     */
    private static function height(float $peak, float $slow, float $slowPeak): float
    {
        return max(array_map(static fn (int $since) => self::curve($since, $peak, $slow, $slowPeak), range(5, 300, 5)));
    }

    /**
     * The area under a curve brought to a peak of 1.
     */
    private static function area(float $peak, float $slow, float $slowPeak): float
    {
        return array_sum(array_map(static fn (int $since) => self::curve($since, $peak, $slow, $slowPeak), range(5, 600, 5))) * 5 / self::height($peak, $slow, $slowPeak);
    }

    private static function proteinArea(float $peak): float
    {
        return array_sum(array_map(static fn (int $since) => self::proteinCurve($since, $peak), range(5, 600, 5))) * 5;
    }

    /**
     * The area of insulin after 1000 kJ of glucose, whose peak is 100 above base,
     * for a demand of 1.
     */
    private static function referenceArea(): float
    {
        [$slow, $slowPeak] = self::slowHump(self::GLUCOSE_REFERENCE_GRAMS, 100.0);

        return self::area(self::FASTEST_PEAK_MINUTES + self::INSULIN_LAG_MINUTES, $slow, $slowPeak);
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
     * Walking begun after the meal lowers what the meal still does. Movement begun
     * before the meal is movement before it, even if it ended a minute later.
     *
     * @param  array<int, array{minute: int, minutes: int, met: float, type?: string|null}>  $activities
     */
    private static function walkLowers(int $mealMinute, int $moment, array $activities, bool $glucose): float
    {
        $lowered = 0.0;

        foreach ($activities as $activity) {
            if (! self::isAfterMeal($activity, $mealMinute)) {
                continue;
            }

            $start = $activity['minute'];
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
     * Whether this movement counts as a walk after the meal.
     *
     * @param  array{minute: int, minutes: int, met: float, type?: string|null}  $activity
     */
    public static function isAfterMeal(array $activity, int $mealMinute): bool
    {
        return $activity['met'] >= self::WALK_MET
            && $activity['minute'] >= $mealMinute
            && $activity['minute'] < $mealMinute + self::AFTER_MEAL_MINUTES;
    }

    /**
     * Movement before the meal leaves the body more sensitive for two days, by how
     * long and how hard it was.
     *
     * @param  array<int, array{minute: int, minutes: int, met: float, type?: string|null}>  $activities
     */
    private static function sensitivityGain(int $mealMinute, array $activities): float
    {
        $gain = 0.0;

        foreach ($activities as $activity) {
            $since = max(0, $mealMinute - ($activity['minute'] + $activity['minutes']));
            if ($activity['met'] < self::WALK_MET || $activity['minute'] >= $mealMinute || $since > self::GAIN_LASTS_MINUTES) {
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
