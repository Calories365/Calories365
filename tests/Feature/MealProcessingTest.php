<?php

namespace Tests\Feature;

use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * How processed a food is reaches the diary page and the statistics of the bot,
 * and what the model answered under the old rule is asked again.
 */
class MealProcessingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_the_diary_page_gets_the_nova_group_of_each_meal(): void
    {
        $this->meal($this->product('Хлеб', 3));
        $this->meal($this->product('Гречка', null));

        $this->actingAs($this->user)->getJson('/api/meals/2026-10-31')->assertOk()
            ->assertJsonPath('0.nova', 3)
            ->assertJsonPath('1.nova', null);
    }

    public function test_the_statistics_of_the_bot_get_the_nova_group_of_each_meal(): void
    {
        $this->meal($this->product('Колбаса варёная', 4));

        $this->withHeaders(['X-Api-Key' => 'testing-bot-key', 'X-Calories-Id' => $this->user->id, 'X-Locale' => 'ru'])
            ->getJson('/api/caloriesEndPoint/showUserStats/2026-10-31/morning')->assertOk()
            ->assertJsonPath('0.name', 'Колбаса варёная')
            ->assertJsonPath('0.nova', 4);
    }

    public function test_what_the_model_answered_under_the_old_rule_is_asked_again_and_a_label_stays(): void
    {
        $bread = $this->product('Батон', 4);
        $label = $this->product('Хлеб тостовый Harry', 4, Product::SOURCE_LABEL);

        (require database_path('migrations/2026_10_31_120000_ask_again_about_the_nova_group.php'))->up();

        $this->assertSame([null, null], [$bread->fresh()->nova, $bread->fresh()->quality_source]);
        $this->assertSame([4, Product::SOURCE_LABEL], [$label->fresh()->nova, $label->fresh()->quality_source]);
    }

    private function product(string $name, ?int $nova, string $qualitySource = Product::SOURCE_AI): Product
    {
        $product = Product::create([
            'calories' => 250, 'proteins' => 8, 'fats' => 2, 'carbohydrates' => 48, 'fibers' => 2,
            'nova' => $nova, 'quality_source' => $nova === null ? null : $qualitySource,
        ]);

        ProductTranslation::create(['product_id' => $product->id, 'locale' => 'ru', 'name' => $name, 'active' => 1, 'verified' => 0]);

        return $product;
    }

    private function meal(Product $product): void
    {
        FoodConsumption::create([
            'user_id' => $this->user->id, 'food_id' => $product->id, 'quantity' => 100,
            'consumed_at' => '2026-10-31', 'part_of_day' => 'morning',
        ]);
    }
}
