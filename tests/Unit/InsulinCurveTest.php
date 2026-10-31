<?php

namespace Tests\Unit;

use App\Services\InsulinCurve;
use PHPUnit\Framework\TestCase;

/**
 * The estimate of insulin: a fast sweet food lifts it far more than a slow one,
 * and movement and the way of life change how much.
 */
class InsulinCurveTest extends TestCase
{
    private const MODERATE = 3;

    private const BUCKWHEAT = ['grams' => 200.0, 'calories' => 105.0, 'proteins' => 3.0, 'fats' => 1.0, 'carbohydrates' => 20.0, 'sugars' => 0.5, 'glycemic_index' => 50];

    private const BUN_WITH_JAM = ['grams' => 120.0, 'calories' => 340.0, 'proteins' => 7.0, 'fats' => 8.0, 'carbohydrates' => 60.0, 'sugars' => 25.0, 'glycemic_index' => 85];

    public function test_a_gram_of_glucose_in_1000_kj_gives_the_reference_of_the_regression(): void
    {
        $glucose = ['grams' => 62.5, 'calories' => 382.0, 'proteins' => 0.0, 'fats' => 0.0, 'carbohydrates' => 100.0, 'sugars' => 100.0, 'glycemic_index' => 100];

        // -4.2 + 0.9 × 100 + 0.3 × 62.5 = 104.5
        $this->assertEqualsWithDelta(104.5, InsulinCurve::load($glucose), 0.5);
    }

    public function test_a_food_without_carbohydrates_still_asks_for_some_by_its_protein(): void
    {
        $eggs = ['grams' => 100.0, 'calories' => 155.0, 'proteins' => 13.0, 'fats' => 11.0, 'carbohydrates' => 1.0, 'sugars' => 1.0, 'glycemic_index' => 0];

        $this->assertGreaterThan(5, InsulinCurve::load($eggs));
        $this->assertLessThan(InsulinCurve::load(self::BUCKWHEAT), InsulinCurve::load($eggs));
    }

    public function test_a_food_with_nothing_to_ask_for_never_goes_below_zero(): void
    {
        $oil = ['grams' => 10.0, 'calories' => 900.0, 'proteins' => 0.0, 'fats' => 100.0, 'carbohydrates' => 0.0, 'sugars' => 0.0, 'glycemic_index' => 0];

        $this->assertGreaterThanOrEqual(0.0, InsulinCurve::load($oil));
    }

    public function test_a_bun_with_jam_lifts_the_level_far_more_and_sooner_than_buckwheat(): void
    {
        [$buckwheatPeak, $buckwheatMinute] = $this->peakOf(self::BUCKWHEAT);
        [$bunPeak, $bunMinute] = $this->peakOf(self::BUN_WITH_JAM);

        $this->assertLessThan(InsulinCurve::MEDIUM_LEVEL + 15, $buckwheatPeak);
        $this->assertGreaterThan(InsulinCurve::HIGH_LEVEL, $bunPeak);
        $this->assertGreaterThan(2.0, ($bunPeak - InsulinCurve::BASE_LEVEL) / ($buckwheatPeak - InsulinCurve::BASE_LEVEL));
        $this->assertLessThan($buckwheatMinute, $bunMinute);
    }

    public function test_the_level_returns_to_the_base_hours_after_a_meal(): void
    {
        $levels = InsulinCurve::levels([$this->eaten(self::BUN_WITH_JAM, 480)], [], self::MODERATE, [470, 780, 1200]);

        $this->assertSame(InsulinCurve::BASE_LEVEL, $levels[470]);
        $this->assertLessThan(InsulinCurve::BASE_LEVEL + 10, $levels[780]);
        $this->assertEqualsWithDelta(InsulinCurve::BASE_LEVEL, $levels[1200], 0.2);
    }

    public function test_a_day_without_food_stays_at_the_base(): void
    {
        $levels = InsulinCurve::levels([], [], self::MODERATE, [0, 720, 1440]);

        $this->assertSame([0 => 10.0, 720 => 10.0, 1440 => 10.0], $levels);
    }

    public function test_a_walk_after_the_meal_takes_about_a_quarter_off_what_is_left(): void
    {
        $meal = [$this->eaten(self::BUN_WITH_JAM, 480)];
        $moments = [600];
        $still = InsulinCurve::levels($meal, [], self::MODERATE, $moments)[600] - InsulinCurve::BASE_LEVEL;
        $walked = InsulinCurve::levels($meal, [['minute' => 500, 'minutes' => 28, 'met' => 3.0]], self::MODERATE, $moments)[600] - InsulinCurve::BASE_LEVEL;

        $this->assertEqualsWithDelta(0.23, 1 - $walked / $still, 0.01);
    }

    public function test_sitting_in_a_car_after_the_meal_does_not_count_as_a_walk(): void
    {
        $meal = [$this->eaten(self::BUN_WITH_JAM, 480)];
        $still = InsulinCurve::levels($meal, [], self::MODERATE, [600])[600];
        $drove = InsulinCurve::levels($meal, [['minute' => 500, 'minutes' => 60, 'met' => 1.5]], self::MODERATE, [600])[600];

        $this->assertSame($still, $drove);
    }

    public function test_a_workout_before_the_meal_lowers_it_less_as_the_day_goes_by(): void
    {
        $meal = fn (int $minute) => [$this->eaten(self::BUN_WITH_JAM, $minute)];
        $workout = [['minute' => 300, 'minutes' => 60, 'met' => 8.0]];

        $without = InsulinCurve::levels($meal(480), [], self::MODERATE, [520])[520];
        $soon = InsulinCurve::levels($meal(480), $workout, self::MODERATE, [520])[520];
        $late = InsulinCurve::levels($meal(1300), $workout, self::MODERATE, [1340])[1340];

        $this->assertLessThan($without, $soon);
        $this->assertGreaterThan($soon, $late);
        $this->assertLessThan($without, $late);
    }

    public function test_the_level_falls_while_moving(): void
    {
        $moving = InsulinCurve::levels([], [['minute' => 600, 'minutes' => 30, 'met' => 8.0]], self::MODERATE, [590, 610, 640]);

        $this->assertSame(10.0, $moving[590]);
        $this->assertLessThan(10.0, $moving[610]);
        $this->assertSame(10.0, $moving[640]);
    }

    public function test_the_way_of_life_scales_what_a_meal_does(): void
    {
        $meal = [$this->eaten(self::BUCKWHEAT, 480)];
        $sitting = InsulinCurve::levels($meal, [], 1, [530])[530];
        $active = InsulinCurve::levels($meal, [], 5, [530])[530];

        $this->assertGreaterThan($active, $sitting);
    }

    /**
     * @param  array<string, mixed>  $food
     * @return array{minute: int, load: float, peak_minutes: float}
     */
    private function eaten(array $food, int $minute): array
    {
        return ['minute' => $minute, 'load' => InsulinCurve::load($food), 'peak_minutes' => InsulinCurve::peakMinutes($food)];
    }

    /**
     * @param  array<string, mixed>  $food
     * @return array{0: float, 1: int} the highest level and when it falls
     */
    private function peakOf(array $food): array
    {
        $levels = InsulinCurve::levels([$this->eaten($food, 0)], [], self::MODERATE, range(0, 300, 5));
        $peak = max($levels);

        return [$peak, array_search($peak, $levels, true)];
    }
}
