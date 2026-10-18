<?php

namespace Tests\Unit;

use App\Services\ProductQuality;
use PHPUnit\Framework\TestCase;

/**
 * Sugars, the glycemic index and the NOVA group are kept only when they can be
 * true, and give the marks and the load the draft shows.
 */
class ProductQualityTest extends TestCase
{
    public function test_values_that_cannot_be_true_are_not_kept(): void
    {
        $this->assertSame(
            ['sugars' => null, 'glycemic_index' => null, 'nova' => null],
            ProductQuality::valid(['sugars' => -1, 'glycemic_index' => 140, 'nova' => 5], 40)
        );
    }

    public function test_sugars_never_exceed_the_carbohydrates(): void
    {
        $this->assertSame(12.0, ProductQuality::valid(['sugars' => 30], 12)['sugars']);
    }

    public function test_a_food_almost_without_carbohydrates_has_no_glycemic_index(): void
    {
        $this->assertSame(0, ProductQuality::valid(['glycemic_index' => 45], 0.5)['glycemic_index']);
    }

    public function test_the_glycemic_load_of_a_portion_and_its_marks(): void
    {
        // A bun of 60 g: 30 g of carbohydrates with GI 75.
        $this->assertSame(22.5, ProductQuality::glycemicLoad(75, 30));
        $this->assertTrue(ProductQuality::fastCarbohydrates(75, 30));
        // A spoon of the same carbohydrates says little.
        $this->assertFalse(ProductQuality::fastCarbohydrates(75, 5));
        $this->assertNull(ProductQuality::glycemicLoad(null, 30));
    }

    public function test_the_glycemic_load_of_a_meal_is_low_medium_or_high(): void
    {
        $this->assertSame('low', ProductQuality::mealLoadLevel(18));
        $this->assertSame('medium', ProductQuality::mealLoadLevel(30));
        $this->assertSame('high', ProductQuality::mealLoadLevel(45));
    }
}
