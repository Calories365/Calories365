<?php

namespace Tests\Feature;

use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\User;
use App\Services\ProductEnricher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Products of the database get their sugars, glycemic index and NOVA group
 * from the product model, many at once, and only once.
 */
class ProductEnricherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_products_are_asked_about_in_one_request_with_their_names_and_facts(): void
    {
        $bread = $this->product('Хлеб белый', 46);
        $buckwheat = $this->product('Гречка варёная', 20);
        $this->fakeModel([
            ['index' => 0, 'sugars' => 4, 'glycemic_index' => 75, 'nova' => 4],
            ['index' => 1, 'sugars' => 0.6, 'glycemic_index' => 50, 'nova' => 1],
        ]);

        $done = app(ProductEnricher::class)->enrich([$bread->id, $buckwheat->id]);

        $this->assertSame(2, $done);
        $this->assertSame(75, $bread->fresh()->glycemic_index);
        $this->assertSame(4, $bread->fresh()->nova);
        $this->assertSame('ai', $buckwheat->fresh()->quality_source);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request['model'] === 'gpt-6-luna'
            && str_contains($request['messages'][0]['content'], '0. Хлеб белый — per 100 g: 250 kcal, proteins 5, fats 3, carbohydrates 46'));
    }

    public function test_a_product_that_was_answered_about_is_not_asked_again(): void
    {
        $bread = $this->product('Хлеб белый', 46);
        $bread->update(['glycemic_index' => 75, 'quality_source' => 'ai']);

        $this->assertSame(0, app(ProductEnricher::class)->enrich([$bread->id]));

        Http::assertNothingSent();
    }

    public function test_a_failed_request_leaves_the_products_to_ask_again(): void
    {
        $bread = $this->product('Хлеб белый', 46);
        Http::fake(['api.openai.com/*' => Http::response('', 500)]);

        $this->assertSame(0, app(ProductEnricher::class)->enrich([$bread->id]));

        $this->assertNull($bread->fresh()->quality_source);
    }

    public function test_the_scheduled_command_asks_about_the_products_people_eat(): void
    {
        $eaten = $this->product('Хлеб белый', 46);
        $neverEaten = $this->product('Мука ржаная', 70);
        FoodConsumption::create(['user_id' => User::factory()->create()->id, 'food_id' => $eaten->id, 'quantity' => 50, 'part_of_day' => 'morning', 'consumed_at' => now()]);
        $this->fakeModel([['index' => 0, 'sugars' => 4, 'glycemic_index' => 75, 'nova' => 4]]);

        $this->artisan('products:enrich')->assertSuccessful();

        $this->assertSame(75, $eaten->fresh()->glycemic_index);
        $this->assertNull($neverEaten->fresh()->quality_source);
    }

    private function product(string $name, float $carbohydrates): Product
    {
        $product = Product::create(['calories' => 250, 'proteins' => 5, 'fats' => 3, 'carbohydrates' => $carbohydrates, 'fibers' => 2]);
        ProductTranslation::create(['product_id' => $product->id, 'locale' => 'ru', 'name' => $name, 'user_id' => null, 'active' => 1, 'verified' => 0]);

        return $product;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function fakeModel(array $items): void
    {
        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode(['items' => $items])]]],
        ])]);
    }
}
