<?php

namespace Tests\Feature;

use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\User;
use App\Services\FoodName;
use App\Services\ProductMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Meilisearch\Client;
use Meilisearch\Contracts\SearchQuery;
use Mockery;
use Tests\TestCase;

/**
 * ProductMatcher picks, from the products the index found, the one that is the
 * same food cooked the same way, without asking a model.
 */
class ProductMatcherTest extends TestCase
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

    public function test_it_picks_the_product_cooked_the_way_the_user_said_and_with_nothing_added(): void
    {
        $this->indexed('гречк', [
            ['гречка', 329, 13, 3, 68],
            ['гречка вареная с луком и морковью', 289, 4, 9, 40],
            ['Вареная гречка на воде', 105, 3, 1, 20],
        ]);

        $this->assertSame('Вареная гречка на воде', $this->match('Гречка варёная')['name']);
        $this->assertSame('Вареная гречка на воде', $this->match('Гречка')['name']);
    }

    public function test_without_the_same_food_it_finds_nothing(): void
    {
        $this->indexed('гречк', [['гречка вареная с луком и морковью', 289, 4, 9, 40]]);

        $this->assertNull($this->match('Гречка варёная'));
    }

    public function test_a_product_with_facts_that_cannot_be_true_is_left_out(): void
    {
        $this->indexed('мед', [['мед', 11, 11, 11, 11], ['мед разнотравья', 314, 1, 0, 75]]);
        $this->indexed('манк', [['Манная каша на воде', 329, 11, 1, 65]]);

        $this->assertSame('мед разнотравья', $this->match('Мёд')['name']);
        $this->assertNull($this->match('Каша манная'));
    }

    public function test_the_calories_most_products_of_the_food_agree_on_win(): void
    {
        $this->indexed('кур', [
            ['Курица вареная', 56, 11, 1, 0],
            ['Курица отварная', 170, 25, 7, 0],
            ['Отварная курица', 165, 25, 7, 0],
        ]);

        $this->assertSame(170.0, (float) Product::find($this->match('Курица')['product_id'])->calories);
    }

    public function test_what_the_user_ate_before_comes_first(): void
    {
        $user = User::factory()->create();
        $this->indexed('творог 5', [['Творог 5%', 120, 17, 5, 2], ['Творог 5% Простоквашино', 121, 17, 5, 3]]);
        $hits = $this->index['творог 5'];
        FoodConsumption::create(['user_id' => $user->id, 'food_id' => $hits[1]['product_id'], 'quantity' => 200, 'part_of_day' => 'morning', 'consumed_at' => now()]);

        $this->assertSame('Творог 5%', $this->match('Творог 5%')['name']);
        $this->assertSame('Творог 5% Простоквашино', app(ProductMatcher::class)->match(['Творог 5%'], $user->id, 'ru')[0]['name']);
    }

    public function test_a_name_in_another_language_comes_after_one_in_the_users_language(): void
    {
        $this->indexed('яйц', [['Яйце підсмажене', 196, 14, 15, 1], ['Яйцо жареное', 196, 14, 15, 1]]);

        $this->assertSame('Яйцо жареное', $this->match('Яйцо жареное')['name']);
    }

    public function test_a_failed_search_finds_nothing_but_is_thrown_to_the_catalog(): void
    {
        $matcher = new ProductMatcher(tap(Mockery::mock(Client::class), function ($client) {
            $client->shouldReceive('multiSearch')->andThrow(new \RuntimeException('Meilisearch is down'));
        }));

        $this->assertSame([null, null], $matcher->match(['Гречка', 'Рис'], null, 'ru'));

        $this->expectException(\RuntimeException::class);
        $matcher->find(['Гречка'], null, 'ru');
    }

    public function test_one_request_searches_all_items_by_stems_and_fitting_cooking_methods(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('multiSearch')->once()->andReturnUsing(function (array $queries) {
            $this->assertCount(2, $queries);

            $leg = $queries[0]->toArray();
            $this->assertSame('ножк кур', $leg['q']);
            $this->assertSame(['stems'], $leg['attributesToSearchOn']);
            $this->assertSame('all', $leg['matchingStrategy']);
            $this->assertContains('locale = "ru"', $leg['filter']);
            $this->assertContains('cooking IN ["baked", "grilled", "cooked"]', $leg['filter']);
            $this->assertContains('((user_id = 7 AND active = 1) OR user_id IS NULL OR verified = 1)', $leg['filter']);
            $this->assertContainsOnlyInstancesOf(SearchQuery::class, $queries);

            return ['results' => [['hits' => []], ['hits' => []]]];
        });

        $this->assertSame([null, null], (new ProductMatcher($client))->find(['Нога куриная запечённая', 'Яблоко'], 7, 'ru'));
    }

    /**
     * Products in the database and the hits the index gives for them.
     *
     * @param  array<int, array{0: string, 1: float, 2: float, 3: float, 4: float}>  $products  name, calories, proteins, fats, carbohydrates
     */
    private function indexed(string $queryText, array $products): void
    {
        foreach ($products as [$name, $calories, $proteins, $fats, $carbohydrates]) {
            $product = Product::create(compact('calories', 'proteins', 'fats', 'carbohydrates') + ['fibers' => 0]);

            $this->index[$queryText][] = [
                'id' => $product->id * 10,
                'product_id' => $product->id,
                'locale' => 'ru',
                'name' => $name,
                'user_id' => null,
                'verified' => 0,
            ];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function match(string $name): ?array
    {
        return app(ProductMatcher::class)->match([$name], null, 'ru')[0];
    }
}
