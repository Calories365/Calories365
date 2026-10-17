<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\ProductMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The search button on the voice page looks a product up again by its name; with
 * should_generate the page offers to generate its nutrition facts instead.
 */
class VoiceSearchProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_of_the_same_food_is_returned(): void
    {
        $user = User::factory()->create();
        $baked = Product::create(['calories' => 191, 'proteins' => 23, 'carbohydrates' => 0, 'fats' => 11, 'fibers' => 0]);
        $this->mock(ProductMatcher::class, fn ($mock) => $mock->shouldReceive('match')->once()
            ->with(['Нога запечённая куриная'], $user->id, 'ru')
            ->andReturn([['id' => 12, 'product_id' => $baked->id, 'locale' => 'ru', 'name' => 'куриная ножка в духовке', 'user_id' => null, '_rankingScore' => 0.4]]));

        $this->search($user, 'Нога запечённая куриная')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('should_generate', false)
            ->assertJsonPath('product.product_translation.name', 'куриная ножка в духовке')
            ->assertJsonPath('product.product.calories', 191);
    }

    public function test_without_a_product_of_the_same_food_the_page_is_told_to_generate(): void
    {
        $user = User::factory()->create();
        $this->mock(ProductMatcher::class, fn ($mock) => $mock->shouldReceive('match')->once()->andReturn([null]));

        $this->search($user, 'Гречка варёная')
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('should_generate', true);
    }

    private function search(User $user, string $name)
    {
        return $this->actingAs($user)->postJson('/api/voice/search-product', ['product_name' => $name], ['Accept-Language' => 'ru']);
    }
}
