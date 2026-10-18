<?php

namespace Tests\Feature;

use App\Models\FoodConsumption;
use App\Models\MealDraft;
use App\Models\MealDraftItem;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\User;
use App\Services\ProductCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The meal draft the bot panel builds from voice, text and photos and saves to
 * the diary at once.
 */
class MealDraftTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** @var array<string, Product> products the catalog finds, by name */
    private array $known = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->user = User::factory()->create();

        foreach (['Гречка варёная' => [105, 3, 1, 20], 'Куриная грудка варёная' => [151, 29, 3, 0], 'Хлеб' => [234, 8, 2, 46]] as $name => [$calories, $proteins, $fats, $carbohydrates]) {
            $this->known[$name] = Product::create(compact('calories', 'proteins', 'fats', 'carbohydrates') + ['fibers' => 0]);
        }

        $this->partialMock(ProductCatalog::class, function ($mock) {
            $mock->shouldReceive('find')->andReturnUsing(fn (array $names) => array_map(
                fn (string $name) => isset($this->known[$name])
                    ? ['id' => $this->known[$name]->id * 10, 'product_id' => $this->known[$name]->id, 'locale' => 'ru', 'name' => $name, 'user_id' => null]
                    : null,
                $names
            ));
        });
    }

    public function test_a_meal_said_in_words_becomes_a_draft_of_database_products(): void
    {
        $this->fakeModel([$this->add('Гречка варёная', 200), $this->add('Куриная грудка варёная', 150)]);

        $response = $this->input(['text' => 'гречка 200 грамм и куриная грудка'])->assertOk()
            ->assertJsonPath('understood', true)
            ->assertJsonPath('draft.items.0.number', 1)
            ->assertJsonPath('draft.items.0.name', 'Гречка варёная')
            ->assertJsonPath('draft.items.0.grams', 200)
            ->assertJsonPath('draft.items.0.per_100g.calories', 105)
            ->assertJsonPath('draft.items.0.portion.calories', 210)
            ->assertJsonPath('draft.items.1.portion.proteins', 43.5);

        $this->assertEquals(436.5, $response->json('draft.total.calories'));
        Http::assertSent(fn (Request $request) => $request['model'] === 'gpt-6.1-sol'
            && str_contains($request['messages'][0]['content'][0]['text'], 'Text: гречка 200 грамм и куриная грудка'));
    }

    public function test_an_input_changes_the_items_the_user_means(): void
    {
        $this->draftOf(['Гречка варёная' => 200, 'Хлеб' => 40]);
        $this->fakeModel([
            ['action' => 'update', 'item' => 1, 'name' => '', 'grams' => 250, 'label' => null],
            ['action' => 'remove', 'item' => 2, 'name' => '', 'grams' => null, 'label' => null],
            $this->add('Куриная грудка варёная', 120),
        ]);

        $this->input(['text' => 'гречки было 250, хлеба не было, и ещё грудка'])->assertOk()
            ->assertJsonCount(2, 'draft.items')
            ->assertJsonPath('draft.items.0.grams', 250)
            ->assertJsonPath('draft.items.1.number', 2)
            ->assertJsonPath('draft.items.1.name', 'Куриная грудка варёная');

        Http::assertSent(fn (Request $request) => str_contains($request['messages'][0]['content'][0]['text'], "1. Гречка варёная — 200 g\n2. Хлеб — 40 g"));
    }

    public function test_nutrition_facts_the_user_tells_make_the_items_the_users_own(): void
    {
        $this->draftOf(['Хлеб' => 40]);
        $this->fakeModel([
            ['action' => 'update', 'item' => 1, 'name' => '', 'grams' => null, 'label' => null, 'calories' => 260, 'proteins' => null, 'fats' => null, 'carbohydrates' => null],
            ['action' => 'add', 'item' => null, 'name' => 'Батончик домашний', 'grams' => 50, 'label' => null, 'calories' => 400, 'proteins' => 10, 'fats' => 20, 'carbohydrates' => 45],
        ]);

        $this->input(['text' => 'в хлебе 260 калорий, и батончик 50 грамм: 400 ккал, белки 10, жиры 20, углеводы 45'])->assertOk()
            ->assertJsonPath('draft.items.0.per_100g.calories', 260)
            ->assertJsonPath('draft.items.0.per_100g.proteins', 8)
            ->assertJsonPath('draft.items.0.edited', true)
            ->assertJsonPath('draft.items.1.name', 'Батончик домашний')
            ->assertJsonPath('draft.items.1.product_id', null)
            ->assertJsonPath('draft.items.1.source', MealDraftItem::SOURCE_USER)
            ->assertJsonPath('draft.items.1.portion.calories', 200);

        $this->bot()->postJson('/api/caloriesEndPoint/draft/save', ['part_of_day' => 'supper', 'consumed_at' => '2026-10-18'])->assertOk();

        $this->assertSame([$this->user->id, $this->user->id], FoodConsumption::with('product')->get()->pluck('product.user_id')->all());
    }

    public function test_a_short_edit_needs_no_model(): void
    {
        $this->draftOf(['Гречка варёная' => 200, 'Хлеб' => 40]);

        $this->input(['text' => '2 60'])->assertOk()->assertJsonPath('draft.items.1.grams', 60);
        $this->input(['text' => 'убрать 1'])->assertOk()
            ->assertJsonCount(1, 'draft.items')
            ->assertJsonPath('draft.items.0.name', 'Хлеб');

        Http::assertNothingSent();
    }

    public function test_a_label_photo_makes_a_shared_product_with_the_printed_facts(): void
    {
        $this->fakeModel([$this->add('Кефир Олимп 1%', 250, label: 0)], [$this->label(named: true)]);

        $this->input(['text' => 'выпил стакан кефира', 'photo' => $this->photo()])->assertOk()
            ->assertJsonPath('draft.items.0.name', 'Кефир Олимп 1%')
            ->assertJsonPath('draft.items.0.source', MealDraftItem::SOURCE_LABEL)
            ->assertJsonPath('draft.items.0.per_100g.calories', 39);

        $product = Product::where('source', Product::SOURCE_LABEL)->sole();
        $this->assertNull($product->user_id);
        $this->assertSame(4.1, $product->sugars);
        $this->assertSame(Product::SOURCE_LABEL, $product->quality_source);
        $this->assertSame(['en' => 'Kefir Olimp 1%', 'ru' => 'Кефир Олимп 1%', 'ua' => 'Кефір Олімп 1%'], $product->translations()->orderBy('locale')->pluck('name', 'locale')->all());
        $this->assertDatabaseHas('product_aliases', ['product_id' => $product->id]);
        Http::assertSent(fn (Request $request) => str_starts_with($request['messages'][0]['content'][1]['image_url']['url'], 'data:image/jpeg;base64,'));
    }

    public function test_the_same_label_photographed_again_finds_the_product_it_made(): void
    {
        $this->fakeModel([$this->add('Кефир Олимп 1%', 250, label: 0)], [$this->label(named: true)]);
        $this->input(['photo' => $this->photo()])->assertOk();

        $again = $this->label(named: true);
        $again['name_ru'] = 'Кефир OLYMPOS 1%';
        $this->fakeModel([$this->add('Кефир OLYMPOS 1%', 200, label: 0)], [$again]);
        $this->input(['photo' => $this->photo()])->assertOk();

        $this->assertSame(1, Product::where('source', Product::SOURCE_LABEL)->count());
    }

    public function test_a_label_without_the_products_name_makes_the_users_own_product(): void
    {
        $this->fakeModel([$this->add('Продукт с этикетки', 100, label: 0)], [$this->label(named: false)]);

        $this->input(['photo' => $this->photo()])->assertOk();

        $product = Product::where('source', Product::SOURCE_LABEL)->sole();
        $this->assertSame($this->user->id, $product->user_id);
        $this->assertSame([$this->user->id], ProductTranslation::where('product_id', $product->id)->pluck('user_id')->unique()->values()->all());
    }

    public function test_a_package_without_legible_facts_is_found_by_its_name(): void
    {
        $this->fakeModel([$this->add('Хлеб', 100, label: 0)], [$this->label(named: true, factsVisible: false)]);

        $this->input(['text' => 'съел хлеб', 'photo' => $this->photo()])->assertOk()
            ->assertJsonPath('draft.items.0.name', 'Хлеб')
            ->assertJsonPath('draft.items.0.source', MealDraftItem::SOURCE_DATABASE)
            ->assertJsonPath('draft.items.0.per_100g.calories', 234);

        $this->assertSame(0, Product::where('source', Product::SOURCE_LABEL)->count());
    }

    public function test_an_input_about_nothing_leaves_the_draft_as_it_was(): void
    {
        $this->fakeModel([]);

        $this->input(['text' => 'привет'])->assertOk()
            ->assertJsonPath('understood', false)
            ->assertJsonPath('draft', null);

        $this->assertSame(0, MealDraft::count());
    }

    public function test_a_food_without_a_product_is_reported_and_left_out(): void
    {
        $this->fakeModel([$this->add('Гречка варёная', 200), $this->add('Стол деревянный', 100)]);

        $this->input(['text' => 'гречка и стол'])->assertOk()
            ->assertJsonCount(1, 'draft.items')
            ->assertJsonPath('not_found', ['Стол деревянный']);
    }

    public function test_a_failed_model_call_keeps_the_draft(): void
    {
        $this->draftOf(['Хлеб' => 40]);
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'overloaded']], 500)]);

        $this->input(['text' => 'и ещё гречка'])->assertStatus(503)->assertJsonPath('message', 'draft_input_failed');

        $this->assertSame(1, MealDraftItem::count());
    }

    public function test_saving_writes_the_meal_and_closes_the_draft(): void
    {
        $draft = $this->draftOf(['Гречка варёная' => 200, 'Хлеб' => 40]);
        $draft->items[1]->update(['calories' => 260, 'edited' => true]);

        $this->bot()->postJson('/api/caloriesEndPoint/draft/save', ['part_of_day' => 'dinner', 'consumed_at' => '2026-10-18'])
            ->assertOk()
            ->assertJsonPath('total.calories', 314);

        $meals = FoodConsumption::where('user_id', $this->user->id)->orderBy('id')->get();
        $this->assertSame([$this->known['Гречка варёная']->id, 200, 'dinner'], [$meals[0]->food_id, $meals[0]->quantity, $meals[0]->part_of_day]);
        $own = Product::find($meals[1]->food_id);
        $this->assertSame([$this->user->id, 260.0], [$own->user_id, (float) $own->calories]);
        $this->assertSame(0, MealDraft::count());
    }

    public function test_an_abandoned_draft_is_not_continued(): void
    {
        $draft = $this->draftOf(['Хлеб' => 40]);
        $draft->forceFill(['updated_at' => now()->subHours(MealDraft::LIFETIME_HOURS + 1)])->saveQuietly();

        $this->bot()->getJson('/api/caloriesEndPoint/draft')->assertOk()->assertJsonPath('draft', null);
        $this->assertSame(0, MealDraftItem::count());
    }

    public function test_the_draft_answers_only_with_the_bot_key(): void
    {
        $this->getJson('/api/caloriesEndPoint/draft', ['X-Calories-Id' => $this->user->id])->assertStatus(401);
    }

    /**
     * @param  array<string, int>  $items  grams by the name of a known product
     */
    private function draftOf(array $items): MealDraft
    {
        $draft = MealDraft::create(['user_id' => $this->user->id]);

        $position = 0;
        foreach ($items as $name => $grams) {
            $product = $this->known[$name];
            $draft->items()->create([
                'position' => ++$position,
                'name' => $name,
                'product_id' => $product->id,
                'quantity' => $grams,
                'calories' => $product->calories,
                'proteins' => $product->proteins,
                'fats' => $product->fats,
                'carbohydrates' => $product->carbohydrates,
                'fibers' => 0,
                'source' => MealDraftItem::SOURCE_DATABASE,
            ]);
        }

        return $draft->fresh();
    }

    /**
     * A photo of one pixel: the model is faked, only the file reaches it.
     */
    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('label.jpg', base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA='
        ));
    }

    private function bot(): static
    {
        return $this->withHeaders(['X-Api-Key' => 'testing-bot-key', 'X-Calories-Id' => $this->user->id, 'X-Locale' => 'ru']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function input(array $data)
    {
        return $this->bot()->post('/api/caloriesEndPoint/draft/input', $data, ['Accept' => 'application/json']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $operations
     * @param  array<int, array<string, mixed>>  $labels
     */
    private function fakeModel(array $operations, array $labels = []): void
    {
        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode(['operations' => $operations, 'labels' => $labels])]]],
        ])]);
    }

    /**
     * @return array<string, mixed>
     */
    private function add(string $name, float $grams, ?int $label = null): array
    {
        return ['action' => 'add', 'item' => null, 'name' => $name, 'grams' => $grams, 'label' => $label];
    }

    /**
     * @return array<string, mixed>
     */
    private function label(bool $named, bool $factsVisible = true): array
    {
        return [
            'facts_visible' => $factsVisible,
            'name_ru' => $named ? 'Кефир Олимп 1%' : 'Продукт с этикетки',
            'name_ua' => $named ? 'Кефір Олімп 1%' : 'Продукт з етикетки',
            'name_en' => $named ? 'Kefir Olimp 1%' : 'Product from a label',
            'named' => $named,
            'calories' => 39,
            'proteins' => 3.4,
            'fats' => 1,
            'carbohydrates' => 4.1,
            'fibers' => 0,
            'sugars' => 4.1,
            'glycemic_index' => 15,
            'nova' => 1,
            'package_grams' => 500,
        ];
    }
}
