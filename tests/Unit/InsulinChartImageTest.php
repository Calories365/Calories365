<?php

namespace Tests\Unit;

use App\Services\InsulinChartImage;
use PHPUnit\Framework\TestCase;

/**
 * The picture of the insulin curve the bot sends: a PNG drawn without GD.
 */
class InsulinChartImageTest extends TestCase
{
    public function test_a_day_is_drawn_as_a_png_telegram_takes(): void
    {
        $png = (new InsulinChartImage)->render($this->day(withMeal: true));

        $size = getimagesizefromstring($png);
        $this->assertSame([1000, 500, IMAGETYPE_PNG], [$size[0], $size[1], $size[2]]);
        $this->assertLessThan(200 * 1024, strlen($png));
    }

    public function test_a_meal_changes_the_picture_and_an_empty_day_is_still_drawn(): void
    {
        $empty = (new InsulinChartImage)->render($this->day(withMeal: false));
        $meal = (new InsulinChartImage)->render($this->day(withMeal: true));

        $this->assertNotFalse(getimagesizefromstring($empty));
        $this->assertNotSame(md5($empty), md5($meal));
    }

    /**
     * @return array<string, mixed>
     */
    private function day(bool $withMeal): array
    {
        $points = array_map(static fn (int $minute) => [
            'minute' => $minute,
            'level' => $withMeal && $minute > 780 && $minute < 960 ? 10 + 120 * sin(($minute - 780) / 180 * M_PI) : 10.0,
        ], range(0, 1440, 5));

        return [
            'zones' => ['medium' => 30, 'high' => 60],
            'points' => $points,
            'now' => ['minute' => 1000, 'level' => 10.0],
            'peak' => ['minute' => 870, 'level' => $withMeal ? 130.0 : 10.0],
            'meals' => $withMeal ? [['minute' => 780]] : [],
            'activities' => [['minute' => 800, 'minutes' => 60]],
        ];
    }
}
