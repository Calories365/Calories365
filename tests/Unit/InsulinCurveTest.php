<?php

namespace Tests\Unit;

use App\Services\InsulinCurve;
use PHPUnit\Framework\TestCase;

/**
 * The estimate of sugar and insulin: a fast sweet food lifts both far more than
 * a slow one, protein and vegetables take some off, and the hour, sleep,
 * movement and the way of life change how much.
 */
class InsulinCurveTest extends TestCase
{
    private const MODERATE = 3;

    private const BUCKWHEAT = ['grams' => 200.0, 'calories' => 105.0, 'proteins' => 3.0, 'fats' => 1.0, 'carbohydrates' => 20.0, 'fibers' => 2.7, 'sugars' => 0.5, 'glycemic_index' => 50];

    private const BUN_WITH_JAM = ['grams' => 120.0, 'calories' => 340.0, 'proteins' => 7.0, 'fats' => 8.0, 'carbohydrates' => 60.0, 'fibers' => 2.0, 'sugars' => 25.0, 'glycemic_index' => 85];

    private const CHICKEN = ['grams' => 200.0, 'calories' => 210.0, 'proteins' => 25.0, 'fats' => 12.0, 'carbohydrates' => 0.0, 'fibers' => 0.0, 'sugars' => 0.0, 'glycemic_index' => 0];

    private const SALAD = ['grams' => 250.0, 'calories' => 20.0, 'proteins' => 1.0, 'fats' => 0.2, 'carbohydrates' => 3.0, 'fibers' => 2.0, 'sugars' => 2.0, 'glycemic_index' => 15];

    public function test_a_gram_of_glucose_in_1000_kj_gives_the_reference_of_the_regression(): void
    {
        $glucose = ['grams' => 1000 / 4.184 / 4, 'calories' => 400.0, 'proteins' => 0.0, 'fats' => 0.0, 'carbohydrates' => 100.0, 'sugars' => 100.0, 'glycemic_index' => 100];

        $this->assertEqualsWithDelta(100.0, InsulinCurve::load($glucose), 0.5);
    }

    public function test_a_known_insulin_index_counts_instead_of_the_regression(): void
    {
        $kefir = ['grams' => 400.0, 'calories' => 50.0, 'proteins' => 3.0, 'fats' => 2.5, 'carbohydrates' => 4.0, 'sugars' => 4.0, 'glycemic_index' => 30];

        $this->assertGreaterThan(2 * InsulinCurve::load($kefir), InsulinCurve::load($kefir + ['insulin_index' => 90]));
    }

    public function test_a_food_without_carbohydrates_still_asks_for_some_insulin_by_its_protein(): void
    {
        $eggs = ['grams' => 120.0, 'calories' => 155.0, 'proteins' => 13.0, 'fats' => 11.0, 'carbohydrates' => 1.0, 'sugars' => 1.0, 'glycemic_index' => 0];

        $this->assertGreaterThan(5, InsulinCurve::load($eggs));
        $this->assertLessThan(InsulinCurve::load(self::BUCKWHEAT), InsulinCurve::load($eggs));
    }

    public function test_a_food_with_nothing_to_ask_for_never_goes_below_zero(): void
    {
        $oil = ['grams' => 10.0, 'calories' => 900.0, 'proteins' => 0.0, 'fats' => 100.0, 'carbohydrates' => 0.0, 'sugars' => 0.0, 'glycemic_index' => 0];

        $this->assertGreaterThanOrEqual(0.0, InsulinCurve::load($oil));
    }

    public function test_a_bun_with_jam_lifts_sugar_and_insulin_far_more_and_sooner_than_buckwheat(): void
    {
        [$bunSugar, $bunInsulin, $bunAt] = $this->peaksOf([self::BUN_WITH_JAM]);
        [$buckwheatSugar, $buckwheatInsulin, $buckwheatAt] = $this->peaksOf([self::BUCKWHEAT]);

        $this->assertGreaterThan(InsulinCurve::GLUCOSE_HIGH, $bunSugar);
        $this->assertLessThan(7.0, $buckwheatSugar);
        $this->assertGreaterThan(2, ($bunSugar - InsulinCurve::GLUCOSE_BASE) / ($buckwheatSugar - InsulinCurve::GLUCOSE_BASE));
        $this->assertGreaterThan(InsulinCurve::HIGH_LEVEL, $bunInsulin);
        $this->assertLessThan(InsulinCurve::HIGH_LEVEL, $buckwheatInsulin);
        $this->assertLessThanOrEqual($buckwheatAt, $bunAt);
    }

    public function test_protein_and_vegetables_with_the_bun_take_some_sugar_off(): void
    {
        $alone = InsulinCurve::meal([self::BUN_WITH_JAM]);
        $withLunch = InsulinCurve::meal([self::BUN_WITH_JAM, self::CHICKEN, self::SALAD]);

        $this->assertLessThan($alone['glucose_rise'], $withLunch['glucose_rise']);
        $this->assertGreaterThan(10, $withLunch['lowered']['proteins']);
        $this->assertGreaterThan(0, $withLunch['lowered']['fibers']);
        $this->assertEqualsWithDelta($alone['glycemic_load'] + InsulinCurve::glycemicLoad(self::SALAD), $withLunch['glycemic_load'], 0.1);
    }

    public function test_sugar_of_a_fast_meal_dips_below_the_fasting_level_and_returns(): void
    {
        $levels = InsulinCurve::levels([$this->eaten([self::BUN_WITH_JAM], 480)], [], self::MODERATE, [470, 680, 1200]);

        $this->assertSame(InsulinCurve::GLUCOSE_BASE, $levels['glucose'][470]);
        $this->assertLessThan(InsulinCurve::GLUCOSE_BASE - 0.2, $levels['glucose'][680]);
        $this->assertEqualsWithDelta(InsulinCurve::GLUCOSE_BASE, $levels['glucose'][1200], 0.05);
        $this->assertEqualsWithDelta(InsulinCurve::BASE_LEVEL, $levels['insulin'][1200], 0.2);
    }

    public function test_a_day_without_food_stays_at_the_base(): void
    {
        $levels = InsulinCurve::levels([], [], self::MODERATE, [0, 720, 1440]);

        $this->assertSame([0 => 10.0, 720 => 10.0, 1440 => 10.0], $levels['insulin']);
        $this->assertSame([0 => 5.0, 720 => 5.0, 1440 => 5.0], $levels['glucose']);
    }

    public function test_the_same_meal_in_the_evening_lifts_sugar_more(): void
    {
        $morning = max(InsulinCurve::levels([$this->eaten([self::BUN_WITH_JAM], 480)], [], self::MODERATE, range(480, 700, 5))['glucose']);
        $evening = max(InsulinCurve::levels([$this->eaten([self::BUN_WITH_JAM], 1200)], [], self::MODERATE, range(1200, 1420, 5))['glucose']);

        $this->assertEqualsWithDelta(1.17, ($evening - InsulinCurve::GLUCOSE_BASE) / ($morning - InsulinCurve::GLUCOSE_BASE), 0.02);
    }

    public function test_a_walk_after_the_meal_takes_about_an_eighth_off_what_is_left(): void
    {
        $meal = [$this->eaten([self::BUN_WITH_JAM], 480)];
        $moments = [600];
        $still = InsulinCurve::levels($meal, [], self::MODERATE, $moments)['insulin'][600] - InsulinCurve::BASE_LEVEL;
        $walked = InsulinCurve::levels($meal, [['minute' => 500, 'minutes' => 28, 'met' => 2.8]], self::MODERATE, $moments)['insulin'][600] - InsulinCurve::BASE_LEVEL;

        $this->assertEqualsWithDelta(0.885, $walked / $still, 0.01);
    }

    public function test_a_walk_begun_before_the_meal_is_not_a_walk_after_it_though_it_ended_a_minute_later(): void
    {
        $meal = [$this->eaten([self::BUN_WITH_JAM], 1168)];
        $shop = [['minute' => 1049, 'minutes' => 120, 'met' => 2.5]];
        $shopThatEndedBefore = [['minute' => 1048, 'minutes' => 120, 'met' => 2.5]];

        $this->assertSame(
            InsulinCurve::levels($meal, $shopThatEndedBefore, self::MODERATE, [1230])['glucose'][1230],
            InsulinCurve::levels($meal, $shop, self::MODERATE, [1230])['glucose'][1230]
        );
    }

    public function test_a_long_walk_before_the_meal_lowers_insulin_more_than_sugar(): void
    {
        $meal = [$this->eaten([self::BUN_WITH_JAM], 1168)];
        $shop = [['minute' => 1048, 'minutes' => 120, 'met' => 2.5]];
        $without = InsulinCurve::levels($meal, [], self::MODERATE, [1210]);
        $with = InsulinCurve::levels($meal, $shop, self::MODERATE, [1210]);

        $sugar = 1 - ($with['glucose'][1210] - InsulinCurve::GLUCOSE_BASE) / ($without['glucose'][1210] - InsulinCurve::GLUCOSE_BASE);
        $insulin = 1 - ($with['insulin'][1210] - InsulinCurve::BASE_LEVEL) / ($without['insulin'][1210] - InsulinCurve::BASE_LEVEL);

        $this->assertEqualsWithDelta(0.12, $insulin, 0.01);
        $this->assertEqualsWithDelta(0.03, $sugar, 0.01);
    }

    public function test_62_grams_of_glucose_peak_within_the_hour_return_and_dip(): void
    {
        $glucose = ['grams' => 62.5, 'calories' => 400.0, 'proteins' => 0.0, 'fats' => 0.0, 'carbohydrates' => 100.0, 'fibers' => 0.0, 'sugars' => 100.0, 'glycemic_index' => 100];

        // Eaten in the morning, when the hour adds nothing.
        $levels = InsulinCurve::levels([$this->eaten([$glucose], 450)], [], self::MODERATE, range(450, 750, 5));
        $peak = max($levels['glucose']);
        $peakAt = array_search($peak, $levels['glucose'], true) - 450;

        $this->assertEqualsWithDelta(8.5, $peak, 0.2);
        $this->assertGreaterThanOrEqual(30, $peakAt);
        $this->assertLessThanOrEqual(60, $peakAt);
        $this->assertLessThan(6.0, $levels['glucose'][450 + 120]);
        $this->assertLessThan(InsulinCurve::GLUCOSE_BASE, $levels['glucose'][450 + 180]);
        $this->assertEqualsWithDelta(100, max($levels['insulin']) - InsulinCurve::BASE_LEVEL, 5);
    }

    public function test_an_ordinary_meal_gives_what_healthy_adults_measure(): void
    {
        $meal = ['grams' => 100.0, 'calories' => 800.0, 'proteins' => 34.6, 'fats' => 37.6, 'carbohydrates' => 74.0, 'fibers' => 0.0, 'sugars' => 15.0, 'glycemic_index' => 60];

        // The lunch of Freckmann et al. 2007: +2.4 mmol/L at 47 minutes, in plasma terms.
        $levels = InsulinCurve::levels([$this->eaten([$meal], 750)], [], self::MODERATE, range(750, 1110, 5));
        $peak = max($levels['glucose']);
        $peakAt = array_search($peak, $levels['glucose'], true) - 750;

        $this->assertEqualsWithDelta(2.4, $peak - InsulinCurve::GLUCOSE_BASE, 0.4);
        $this->assertGreaterThanOrEqual(40, $peakAt);
        $this->assertLessThanOrEqual(60, $peakAt);
        $this->assertLessThan(InsulinCurve::GLUCOSE_BASE + 0.3, $levels['glucose'][750 + 240]);
        $this->assertLessThan(InsulinCurve::BASE_LEVEL + 12, $levels['insulin'][750 + 240]);
    }

    public function test_the_insulin_of_protein_is_told_apart_from_that_of_carbohydrates(): void
    {
        $levels = InsulinCurve::levels([$this->eaten([self::CHICKEN], 0)], [], self::MODERATE, range(0, 300, 5));

        $this->assertSame(InsulinCurve::BASE_LEVEL, max($levels['insulin_of_carbohydrates']));
        $this->assertGreaterThan(InsulinCurve::BASE_LEVEL + 10, max($levels['insulin']));
        $this->assertSame(InsulinCurve::GLUCOSE_BASE, max($levels['glucose']));
    }

    public function test_a_food_with_only_carbohydrates_has_no_insulin_of_protein(): void
    {
        $this->assertSame(1.0, InsulinCurve::carbohydrateShare(['grams' => 100.0, 'proteins' => 0.0, 'fats' => 0.0, 'carbohydrates' => 50.0, 'sugars' => 50.0, 'glycemic_index' => 65]));
        $this->assertSame(0.0, InsulinCurve::carbohydrateShare(['grams' => 100.0, 'proteins' => 25.0, 'fats' => 12.0, 'carbohydrates' => 0.0, 'sugars' => 0.0, 'glycemic_index' => 0]));
    }

    public function test_sitting_in_a_car_after_the_meal_does_not_count_as_a_walk(): void
    {
        $meal = [$this->eaten([self::BUN_WITH_JAM], 480)];
        $still = InsulinCurve::levels($meal, [], self::MODERATE, [600]);
        $drove = InsulinCurve::levels($meal, [['minute' => 500, 'minutes' => 60, 'met' => 2.0]], self::MODERATE, [600]);

        $this->assertSame($still, $drove);
    }

    public function test_a_workout_before_the_meal_lowers_it_less_as_the_days_go_by(): void
    {
        $workout = [['minute' => 400, 'minutes' => 60, 'met' => 8.0]];
        $at = fn (int $minute) => InsulinCurve::levels([$this->eaten([self::BUN_WITH_JAM], $minute)], $workout, self::MODERATE, [$minute + 40])['glucose'][$minute + 40];
        $without = InsulinCurve::levels([$this->eaten([self::BUN_WITH_JAM], 480)], [], self::MODERATE, [520])['glucose'][520];

        $this->assertLessThan($without, $at(480));
        $this->assertLessThan($at(480 + 1440), $at(480));
        $this->assertSame(InsulinCurve::levels([$this->eaten([self::BUN_WITH_JAM], 480 + 2880)], [], self::MODERATE, [520 + 2880])['glucose'][520 + 2880], $at(480 + 2880));
    }

    public function test_a_short_night_makes_the_next_meals_lift_more_and_a_nap_does_not(): void
    {
        $meal = [$this->eaten([self::BUN_WITH_JAM], 600)];
        $rested = InsulinCurve::levels($meal, [['minute' => -60, 'minutes' => 480, 'met' => 1.0, 'type' => 'sleep']], self::MODERATE, [640]);
        $short = InsulinCurve::levels($meal, [['minute' => 180, 'minutes' => 240, 'met' => 1.0, 'type' => 'sleep']], self::MODERATE, [640]);
        $nap = InsulinCurve::levels($meal, [['minute' => -60, 'minutes' => 480, 'met' => 1.0, 'type' => 'sleep'], ['minute' => 500, 'minutes' => 40, 'met' => 1.0, 'type' => 'sleep']], self::MODERATE, [640]);

        $this->assertEqualsWithDelta(1.25, ($short['glucose'][640] - InsulinCurve::GLUCOSE_BASE) / ($rested['glucose'][640] - InsulinCurve::GLUCOSE_BASE), 0.01);
        $this->assertSame($rested, $nap);
    }

    public function test_insulin_falls_while_moving(): void
    {
        $moving = InsulinCurve::levels([], [['minute' => 600, 'minutes' => 30, 'met' => 8.0]], self::MODERATE, [590, 610, 640])['insulin'];

        $this->assertSame(10.0, $moving[590]);
        $this->assertLessThan(10.0, $moving[610]);
        $this->assertSame(10.0, $moving[640]);
    }

    public function test_the_way_of_life_scales_what_a_meal_does(): void
    {
        $meal = [$this->eaten([self::BUCKWHEAT], 480)];
        $sitting = InsulinCurve::levels($meal, [], 1, [530]);
        $active = InsulinCurve::levels($meal, [], 5, [530]);

        $this->assertGreaterThan($active['insulin'][530], $sitting['insulin'][530]);
        $this->assertGreaterThan($active['glucose'][530], $sitting['glucose'][530]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $foods
     * @return array<string, mixed>
     */
    private function eaten(array $foods, int $minute): array
    {
        return ['minute' => $minute] + InsulinCurve::meal($foods);
    }

    /**
     * @param  array<int, array<string, mixed>>  $foods
     * @return array{0: float, 1: float, 2: int} the peaks of sugar and insulin, the minute of the sugar peak
     */
    private function peaksOf(array $foods): array
    {
        $levels = InsulinCurve::levels([$this->eaten($foods, 0)], [], self::MODERATE, range(0, 300, 5));
        $sugar = max($levels['glucose']);

        return [$sugar, max($levels['insulin']), array_search($sugar, $levels['glucose'], true)];
    }
}
