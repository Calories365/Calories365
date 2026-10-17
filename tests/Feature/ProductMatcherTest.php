<?php

namespace Tests\Feature;

use App\Services\ProductMatcher;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductMatcherTest extends TestCase
{
    /** @var array<string, array<int, array<string, mixed>>> search hits per query */
    private array $index = [
        'Нога запечённая куриная' => [],
        'куриная ножка запечённая' => [
            ['id' => 11, 'product_id' => 1, 'name' => 'Куриная ножка сырая', '_rankingScore' => 0.9],
            ['id' => 12, 'product_id' => 2, 'name' => 'куриная ножка в духовке', '_rankingScore' => 0.7],
        ],
        'Гречка варёная' => [
            ['id' => 21, 'product_id' => 3, 'name' => 'гречка вареная с луком и морковью', '_rankingScore' => 0.83],
            ['id' => 22, 'product_id' => 4, 'name' => 'Вареная гречка на воде', '_rankingScore' => 0.79],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        app()->setLocale('ru');

        $this->partialMock(ProductMatcher::class, function ($mock) {
            $mock->shouldReceive('candidates')->andReturnUsing(fn (string $query) => $this->index[$query] ?? []);
        });
    }

    public function test_it_picks_the_product_with_the_same_cooking_method(): void
    {
        $this->fakeModel(
            ['items' => [['index' => 0, 'queries' => ['куриная ножка запечённая']], ['index' => 1, 'queries' => []]]],
            ['items' => [['index' => 0, 'candidate_id' => 12], ['index' => 1, 'candidate_id' => 22]]],
        );

        $hits = app(ProductMatcher::class)->match(['Нога запечённая куриная', 'Гречка варёная'], null, 'ru');

        $this->assertSame('куриная ножка в духовке', $hits[0]['name']);
        $this->assertSame('Вареная гречка на воде', $hits[1]['name']);
    }

    public function test_candidates_found_by_the_suggested_names_are_offered_to_the_model(): void
    {
        $this->fakeModel(
            ['items' => [['index' => 0, 'queries' => ['куриная ножка запечённая']]]],
            ['items' => [['index' => 0, 'candidate_id' => 12]]],
        );

        app(ProductMatcher::class)->match(['Нога запечённая куриная'], null, 'ru');

        Http::assertSent(fn (Request $request) => $request['response_format']['json_schema']['name'] === 'product_choice'
            && str_contains($request['messages'][0]['content'], '12: куриная ножка в духовке')
            && $request['model'] === 'gpt-6-luna');
    }

    public function test_no_fitting_candidate_gives_no_product(): void
    {
        $this->fakeModel(
            ['items' => [['index' => 0, 'queries' => []]]],
            ['items' => [['index' => 0, 'candidate_id' => null]]],
        );

        $this->assertSame([null], app(ProductMatcher::class)->match(['Гречка варёная'], null, 'ru'));
    }

    public function test_an_id_that_was_not_offered_for_the_item_is_ignored(): void
    {
        $this->fakeModel(
            ['items' => [['index' => 0, 'queries' => []]]],
            ['items' => [['index' => 0, 'candidate_id' => 12]]],
        );

        $this->assertSame([null], app(ProductMatcher::class)->match(['Гречка варёная'], null, 'ru'));
    }

    public function test_without_the_model_the_best_hit_above_the_old_threshold_is_used(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'down'], 500)]);

        $hits = app(ProductMatcher::class)->match(['Гречка варёная', 'Нога запечённая куриная'], null, 'ru');

        $this->assertSame('гречка вареная с луком и морковью', $hits[0]['name']);
        $this->assertNull($hits[1]);
    }

    /**
     * @param  array<string, mixed>  $queries  answer to the search-queries request
     * @param  array<string, mixed>  $choice  answer to the product-choice request
     */
    private function fakeModel(array $queries, array $choice): void
    {
        Http::fake(['api.openai.com/*' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => json_encode($queries, JSON_UNESCAPED_UNICODE)]]]])
            ->push(['choices' => [['message' => ['content' => json_encode($choice, JSON_UNESCAPED_UNICODE)]]]]),
        ]);
    }
}
