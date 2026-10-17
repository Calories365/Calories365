<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\ProductTranslation;
use App\Services\FoodName;
use App\Services\ProductCatalog;
use App\Services\ProductMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ProductCatalog adds to the database the foods of a meal it lacks, asking the
 * model once, and finds them there the next time.
 */
class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, array<int, array<string, mixed>>> index hits per search text */
    private array $index = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        $this->partialMock(ProductMatcher::class, function ($mock) {
            $mock->shouldReceive('search')->andReturnUsing(fn (array $said) => array_map(
                fn (FoodName $name) => $this->index[$name->queryText()] ?? [],
                $said
            ));
        });
    }

    public function test_a_food_the_database_lacks_is_added_once_and_found_there_again(): void
    {
        $this->fakeModel([$this->item(0, 'Груша запечённая', 'Груша запечена', 'Baked pear', 57)]);

        $first = $this->find(['Груша запечённая'])[0];

        $product = Product::with('translations')->find($first['product_id']);
        $this->assertSame(Product::SOURCE_AI, $product->source);
        $this->assertNull($product->user_id);
        $this->assertSame(57.0, (float) $product->calories);
        $this->assertSame(
            ['en' => 'Baked pear', 'ru' => 'Груша запечённая', 'ua' => 'Груша запечена'],
            $product->translations->sortBy('locale')->pluck('name', 'locale')->all()
        );
        $this->assertSame('Груша запечённая', $first['name']);

        $again = $this->find(['Груша печёная'])[0];

        $this->assertSame($first['product_id'], $again['product_id']);
        Http::assertSentCount(1);
    }

    public function test_the_missing_foods_of_a_meal_go_to_the_model_in_one_request(): void
    {
        $this->fakeModel([
            $this->item(0, 'Салат Цезарь', 'Салат Цезар', 'Caesar salad', 160),
            $this->item(1, 'Стейк говяжий жареный', 'Стейк яловичий смажений', 'Fried beef steak', 250),
        ]);

        $hits = $this->find(['Салат Цезарь', 'Стейк говяжий', 'Салат Цезарь']);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request['model'] === 'gpt-6-luna'
            && $request['response_format']['json_schema']['name'] === 'missing_products'
            && str_contains($request['messages'][0]['content'], "0. Салат Цезарь\n1. Стейк говяжий")
            && ! str_contains($request['messages'][0]['content'], '2. '));
        $this->assertSame($hits[0]['product_id'], $hits[2]['product_id']);
        $this->assertSame(2, Product::count());
    }

    public function test_a_said_name_the_new_product_does_not_match_is_found_by_its_alias(): void
    {
        $this->fakeModel([$this->item(0, 'Стейк говяжий жареный', 'Стейк яловичий смажений', 'Fried beef steak', 250)]);

        $first = $this->find(['Стейк говяжий'])[0];

        $this->assertDatabaseHas('product_aliases', ['locale' => 'ru', 'name' => ProductAlias::keyFor('Стейк говяжий'), 'product_id' => $first['product_id']]);
        $this->assertSame($first['product_id'], $this->find(['Стейк говяжий'])[0]['product_id']);
        Http::assertSentCount(1);
    }

    public function test_a_food_the_database_has_under_the_generated_name_is_not_added_again(): void
    {
        $known = Product::create(['calories' => 191, 'proteins' => 23, 'fats' => 11, 'carbohydrates' => 0, 'fibers' => 0]);
        $translation = ProductTranslation::create(['product_id' => $known->id, 'locale' => 'ru', 'name' => 'Куриная ножка запеченная', 'user_id' => null, 'active' => 1, 'verified' => 0]);
        $this->index['кур ножк'] = [['id' => $translation->id, 'product_id' => $known->id, 'locale' => 'ru', 'name' => 'Куриная ножка запеченная', 'user_id' => null, 'verified' => 0]];
        $this->fakeModel([$this->item(0, 'Куриная ножка запечённая', 'Куряча ніжка запечена', 'Baked chicken leg', 190)]);

        $hit = $this->find(['Окорочок из духовки 221'])[0];

        $this->assertSame($known->id, $hit['product_id']);
        $this->assertSame(1, Product::count());
        $this->assertDatabaseHas('product_aliases', ['product_id' => $known->id]);
    }

    public function test_what_is_not_a_food_or_has_facts_that_cannot_be_true_is_not_added(): void
    {
        $this->fakeModel([
            ['index' => 0, 'is_food' => false] + $this->item(0, 'Стол', 'Стіл', 'Table', 0),
            $this->item(1, 'Мёд', 'Мед', 'Honey', 11, proteins: 11, fats: 11, carbohydrates: 11),
        ]);

        $this->assertSame([null, null], $this->find(['Стол деревянный', 'Мёд']));
        $this->assertSame(0, Product::count());
    }

    public function test_a_failed_search_asks_the_model_nothing(): void
    {
        $this->partialMock(ProductMatcher::class, function ($mock) {
            $mock->shouldReceive('search')->andThrow(new \RuntimeException('Meilisearch is down'));
        });

        $this->assertSame([null], $this->find(['Гречка']));
        Http::assertNothingSent();
    }

    public function test_a_failed_model_call_leaves_the_items_not_found(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'overloaded']], 500)]);

        $this->assertSame([null], $this->find(['Салат Цезарь']));
        $this->assertSame(0, Product::count());
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, array<string, mixed>|null>
     */
    private function find(array $names): array
    {
        return app(ProductCatalog::class)->find($names, null, 'ru');
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

    /**
     * @return array<string, mixed>
     */
    private function item(int $index, string $ru, string $ua, string $en, float $calories, float $proteins = 1, float $fats = 1, float $carbohydrates = 10): array
    {
        return [
            'index' => $index,
            'is_food' => true,
            'name_ru' => $ru,
            'name_ua' => $ua,
            'name_en' => $en,
            'calories' => $calories,
            'proteins' => $proteins,
            'fats' => $fats,
            'carbohydrates' => $carbohydrates,
            'fibers' => 0,
        ];
    }
}
