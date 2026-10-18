<?php

namespace Tests\Feature;

use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\ProductTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A product a user saves with nutrition facts the model estimated, and did not
 * change, joins the shared database; one the user typed in or changed stays the
 * user's own.
 */
class ProductSharingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_a_product_the_bot_generated_and_the_user_kept_is_shared(): void
    {
        $this->saveFromBot('Салат Цезарь', verified: 1)->assertCreated();

        $product = Product::sole();
        $this->assertNull($product->user_id);
        $this->assertSame(Product::SOURCE_AI, $product->source);
        $this->assertSame([null], ProductTranslation::pluck('user_id')->unique()->values()->all());
        $this->assertSame(0, (int) ProductTranslation::max('verified'));
        $this->assertSame($this->user->id, FoodConsumption::sole()->user_id);
        $this->assertDatabaseHas('product_aliases', ['locale' => 'ru', 'name' => ProductAlias::keyFor('Салат Цезарь'), 'product_id' => $product->id]);
    }

    public function test_a_product_the_user_changed_in_the_bot_stays_the_users_own(): void
    {
        $this->saveFromBot('Салат Цезарь', verified: 0)->assertCreated();

        $this->assertOwnProduct();
    }

    public function test_a_generated_product_with_facts_that_cannot_be_true_stays_the_users_own(): void
    {
        $this->saveFromBot('Мёд', verified: 1, calories: 11, proteins: 11, fats: 11, carbohydrates: 11)->assertCreated();

        $this->assertOwnProduct();
    }

    public function test_a_generated_product_named_like_a_shared_one_stays_the_users_own(): void
    {
        $shared = Product::create(['calories' => 190, 'proteins' => 7, 'fats' => 14, 'carbohydrates' => 8, 'fibers' => 0]);
        ProductTranslation::create(['product_id' => $shared->id, 'locale' => 'ru', 'name' => 'Салат Цезарь', 'user_id' => null, 'active' => 1, 'verified' => 0]);

        $this->saveFromBot('Салат Цезарь', verified: 1)->assertCreated();

        $this->assertSame($this->user->id, Product::latest('id')->first()->user_id);
    }

    public function test_a_product_typed_in_on_the_site_stays_the_users_own(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/user-meals', $this->facts('Салат Цезарь') + ['verified' => 1], ['Accept-Language' => 'ru'])
            ->assertCreated();

        $this->assertOwnProduct();
    }

    public function test_a_product_generated_on_the_voice_page_and_kept_is_shared(): void
    {
        $this->saveFromVoicePage(['name' => 'Салат Цезарь', 'isGenerated' => true, 'isModified' => false])->assertOk();

        $this->assertNull(Product::sole()->user_id);
        $this->assertSame(Product::SOURCE_AI, Product::sole()->source);
    }

    public function test_a_product_changed_on_the_voice_page_stays_the_users_own(): void
    {
        $this->saveFromVoicePage(['name' => 'Салат Цезарь', 'isGenerated' => true, 'isModified' => true])->assertOk();

        $this->assertOwnProduct();
    }

    public function test_a_found_product_kept_on_the_voice_page_is_only_eaten(): void
    {
        $found = Product::create(['calories' => 105, 'proteins' => 3, 'fats' => 1, 'carbohydrates' => 20, 'fibers' => 0]);

        $this->saveFromVoicePage(['name' => 'Гречка', 'product_id' => $found->id, 'isGenerated' => false, 'isModified' => false, 'weight' => 250])->assertOk();

        $this->assertSame(1, Product::count());
        $this->assertSame($found->id, FoodConsumption::sole()->food_id);
        $this->assertSame(250, FoodConsumption::sole()->quantity);
    }

    private function assertOwnProduct(): void
    {
        $product = Product::sole();

        $this->assertSame($this->user->id, $product->user_id);
        $this->assertNull($product->source);
        $this->assertSame([$this->user->id], ProductTranslation::pluck('user_id')->unique()->values()->all());
        $this->assertSame(0, (int) ProductTranslation::max('verified'));
        $this->assertSame(0, ProductAlias::count());
    }

    private function saveFromBot(string $name, int $verified, float $calories = 160, float $proteins = 7, float $fats = 10, float $carbohydrates = 10)
    {
        return $this->postJson(
            '/api/caloriesEndPoint/saveProduct',
            $this->facts($name, $calories, $proteins, $fats, $carbohydrates) + ['user_id' => 32, 'verified' => $verified],
            ['X-Api-Key' => 'testing-bot-key', 'X-Calories-Id' => $this->user->id, 'X-Locale' => 'ru']
        );
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function saveFromVoicePage(array $product)
    {
        return $this->actingAs($this->user)->postJson('/api/voice/save-products', [
            'meal_type' => 'morning',
            'products' => [$product + ['calories' => 160, 'protein' => 7, 'fats' => 10, 'carbs' => 10, 'fibers' => 0, 'weight' => 200]],
        ], ['Accept-Language' => 'ru']);
    }

    /**
     * @return array<string, mixed>
     */
    private function facts(string $name, float $calories = 160, float $proteins = 7, float $fats = 10, float $carbohydrates = 10): array
    {
        return [
            'name' => $name,
            'calories' => $calories,
            'proteins' => $proteins,
            'fats' => $fats,
            'carbohydrates' => $carbohydrates,
            'fibers' => 0,
            'quantity' => 200,
            'consumed_at' => '2026-10-18',
            'part_of_day' => 'morning',
        ];
    }
}
