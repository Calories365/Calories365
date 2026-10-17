<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\ProductMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/caloriesEndPoint: the bot panel sends the analysed text of a voice
 * message and shows what comes back. The shape of the answer is the contract
 * with SaveCallbackQueryHandler and AudioMessageHandler of the panel.
 */
class BotStoreFilteredTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $baked;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->baked = Product::create(['calories' => 191, 'proteins' => 23, 'carbohydrates' => 0, 'fats' => 11, 'fibers' => 0]);
    }

    public function test_items_are_matched_together_and_keep_the_answer_shape(): void
    {
        $this->mock(ProductMatcher::class, function ($mock) {
            $mock->shouldReceive('match')->once()
                ->with(['Нога запечённая куриная', 'Гречка варёная'], $this->user->id, 'ru')
                ->andReturn([
                    ['id' => 12, 'product_id' => $this->baked->id, 'locale' => 'ru', 'name' => 'куриная ножка в духовке'],
                    null,
                ]);
        });

        $response = $this->send("Нога запечённая куриная - 200 граммов;\nГречка варёная - 150 граммов;")
            ->assertOk()
            ->assertJsonPath('message', 'Products found');

        $response->assertJsonPath('products.0.found', true)
            ->assertJsonPath('products.0.product_translation.name', 'куриная ножка в духовке')
            ->assertJsonPath('products.0.product_translation.said_name', 'Нога запечённая куриная')
            ->assertJsonPath('products.0.product.id', $this->baked->id)
            ->assertJsonPath('products.0.product.calories', 191)
            ->assertJsonPath('products.0.quantity_grams', 200);

        $response->assertJsonPath('products.1.found', false)
            ->assertJsonPath('products.1.said_name', 'Гречка варёная')
            ->assertJsonPath('products.1.quantity_grams', 150);
    }

    public function test_an_item_without_a_quantity_is_not_sent_to_the_matcher(): void
    {
        $this->mock(ProductMatcher::class, function ($mock) {
            $mock->shouldReceive('match')->once()->with(['Гречка варёная'], $this->user->id, 'ru')->andReturn([null]);
        });

        $this->send('Хлеб; Гречка варёная - 150 граммов;')
            ->assertOk()
            ->assertJsonPath('products.0.found', false)
            ->assertJsonPath('products.0.said_name', 'Хлеб')
            ->assertJsonPath('products.1.found', false);
    }

    private function send(string $text)
    {
        return $this->postJson('/api/caloriesEndPoint', ['text' => $text], [
            'X-Api-Key' => 'testing-bot-key',
            'X-Calories-Id' => $this->user->id,
            'X-Locale' => 'ru',
        ]);
    }
}
