<?php

namespace Tests\Feature;

use App\Models\MealDraft;
use App\Models\MealDraftInput;
use App\Models\MealDraftItem;
use App\Models\Product;
use App\Models\User;
use App\Services\BotPanelService;
use App\Services\ProductCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Voice messages, texts and photos of dishes and labels wait until the user
 * asks to calculate, and then go to the model in one request: from the bot and
 * from the "Приём пищи" page alike.
 */
class MealDraftCollectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $buckwheat;

    private Product $cheese;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Storage::fake(MealDraftInput::DISK);
        $this->user = User::factory()->create();
        $this->buckwheat = Product::create(['calories' => 105, 'proteins' => 3, 'fats' => 1, 'carbohydrates' => 20, 'fibers' => 0]);
        $this->cheese = Product::create(['calories' => 360, 'proteins' => 24, 'fats' => 29, 'carbohydrates' => 0, 'fibers' => 0]);

        $this->partialMock(ProductCatalog::class, function ($mock) {
            $mock->shouldReceive('find')->andReturnUsing(fn (array $names) => array_map(
                fn (string $name) => $name === 'Гречка варёная' ? $this->hit($this->buckwheat, $name) : null,
                $names
            ));
            $mock->shouldReceive('findBranded')->andReturnUsing(fn (array $foods) => array_map(
                fn (array $food) => $food['brand'] === 'Président' ? $this->hit($this->cheese, $food['name']) : null,
                $foods
            ));
        });
    }

    public function test_inputs_wait_for_the_calculation_and_reach_the_model_in_one_request(): void
    {
        $this->collect(['kind' => 'voice', 'text' => 'гречка двести грамм'])->assertOk()
            ->assertJsonPath('inputs.0.kind', 'voice')
            ->assertJsonPath('inputs.0.text', 'гречка двести грамм')
            ->assertJsonPath('draft', null);
        $this->collect(['photo' => $this->photo()])->assertOk();
        $this->collect(['photo' => $this->photo(), 'text' => 'сыр с этой пачки, 30 грамм'])->assertOk()
            ->assertJsonCount(3, 'inputs')
            ->assertJsonPath('inputs.2.kind', 'photo');

        Http::assertNothingSent();

        $this->fakeModel([
            ['action' => 'add', 'item' => null, 'name' => 'Гречка варёная', 'brand' => '', 'grams' => 200, 'label' => null],
            ['action' => 'add', 'item' => null, 'name' => 'Сыр Président', 'brand' => 'Président', 'grams' => 30, 'label' => null],
        ]);

        $this->bot()->postJson('/api/caloriesEndPoint/draft/calculate')->assertStatus(202)
            ->assertJsonPath('calculation.status', 'done')
            ->assertJsonPath('calculation.understood', true)
            ->assertJsonPath('inputs', [])
            ->assertJsonPath('draft.items.0.name', 'Гречка варёная')
            ->assertJsonPath('draft.items.0.grams', 200)
            ->assertJsonPath('draft.items.1.name', 'Сыр Président')
            ->assertJsonPath('draft.items.1.product_id', $this->cheese->id);

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) {
            $content = $request['messages'][0]['content'];
            $prompt = $content[0]['text'];

            return count($content) === 3
                && str_contains($prompt, "1. Voice message: гречка двести грамм\n2. Photo 1 (attached image 1)\n3. Photo 2 (attached image 2), caption: сыр с этой пачки, 30 грамм")
                && str_starts_with($content[1]['image_url']['url'], 'data:image/jpeg;base64,');
        });
        $this->assertSame([], Storage::disk(MealDraftInput::DISK)->allFiles());
    }

    public function test_the_calculation_corrects_the_draft_it_sees(): void
    {
        $this->draftWith(200);
        $this->collect(['text' => 'гречки было 250'])->assertOk();
        $this->fakeModel([['action' => 'update', 'item' => 1, 'name' => '', 'brand' => '', 'grams' => 250, 'label' => null]]);

        $this->bot()->postJson('/api/caloriesEndPoint/draft/calculate')->assertStatus(202)
            ->assertJsonPath('draft.items.0.grams', 250);

        Http::assertSent(fn (Request $request) => str_contains($request['messages'][0]['content'][0]['text'], '1. Гречка варёная — 200 g'));
    }

    public function test_a_short_edit_applies_at_once(): void
    {
        $this->draftWith(200);

        $this->collect(['text' => '1 300'])->assertOk()
            ->assertJsonPath('draft.items.0.grams', 300)
            ->assertJsonPath('inputs', []);

        Http::assertNothingSent();
    }

    public function test_a_failed_calculation_keeps_the_inputs_to_try_again(): void
    {
        $this->collect(['text' => 'гречка'])->assertOk();
        Http::fake(['api.openai.com/*' => Http::response('', 500)]);

        $this->bot()->postJson('/api/caloriesEndPoint/draft/calculate')->assertStatus(202)
            ->assertJsonPath('calculation.status', 'failed')
            ->assertJsonCount(1, 'inputs');
    }

    public function test_nothing_waiting_is_nothing_to_calculate(): void
    {
        $this->bot()->postJson('/api/caloriesEndPoint/draft/calculate')->assertStatus(422)
            ->assertJsonPath('message', 'nothing_to_calculate');
    }

    public function test_a_calculation_takes_only_so_many_photos(): void
    {
        for ($i = 0; $i < MealDraftInput::MAX_PHOTOS; $i++) {
            $this->collect(['photo' => $this->photo()])->assertOk();
        }

        $this->collect(['photo' => $this->photo()])->assertStatus(422)->assertJsonPath('message', 'too_many_inputs');
        $this->collect(['text' => 'и чай'])->assertOk();
    }

    public function test_cancelling_drops_the_inputs_and_their_photos(): void
    {
        $this->draftWith(200);
        $this->collect(['photo' => $this->photo()])->assertOk();

        $this->bot()->deleteJson('/api/caloriesEndPoint/draft')->assertOk()
            ->assertJsonPath('draft', null)
            ->assertJsonPath('inputs', []);

        $this->assertSame([], Storage::disk(MealDraftInput::DISK)->allFiles());
    }

    public function test_the_page_counts_the_calculation_but_not_a_photo_kept_for_it(): void
    {
        $this->mock(BotPanelService::class, fn ($mock) => $mock->shouldReceive('canTranscribeAudio')->once()->with($this->user->id)->andReturn(true));
        $this->fakeModel([['action' => 'add', 'item' => null, 'name' => 'Гречка варёная', 'brand' => '', 'grams' => 150, 'label' => null]]);

        $id = $this->actingAs($this->user)->post('/api/draft/inputs', ['photo' => $this->photo()], ['Accept' => 'application/json'])
            ->assertOk()
            ->json('inputs.0.id');

        $this->actingAs($this->user)->get("/api/draft/inputs/{$id}/photo")->assertOk();
        $this->actingAs(User::factory()->create())->get("/api/draft/inputs/{$id}/photo")->assertNotFound();

        $this->actingAs($this->user)->postJson('/api/draft/calculate')->assertStatus(202)
            ->assertJsonPath('draft.items.0.grams', 150);
    }

    public function test_an_input_can_be_dropped_before_the_calculation(): void
    {
        $id = $this->collect(['text' => 'гречка'])->json('inputs.0.id');
        $this->collect(['text' => 'и хлеб'])->assertOk();

        $this->actingAs($this->user)->deleteJson("/api/draft/inputs/{$id}")->assertOk()
            ->assertJsonCount(1, 'inputs')
            ->assertJsonPath('inputs.0.text', 'и хлеб');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function collect(array $data)
    {
        return $this->bot()->post('/api/caloriesEndPoint/draft/inputs', $data, ['Accept' => 'application/json']);
    }

    private function bot(): static
    {
        return $this->withHeaders(['X-Api-Key' => 'testing-bot-key', 'X-Calories-Id' => $this->user->id, 'X-Locale' => 'ru']);
    }

    /**
     * @return array<string, mixed>
     */
    private function hit(Product $product, string $name): array
    {
        return ['id' => $product->id * 10, 'product_id' => $product->id, 'locale' => 'ru', 'name' => $name, 'user_id' => null];
    }

    private function draftWith(int $grams): MealDraft
    {
        $draft = MealDraft::create(['user_id' => $this->user->id]);
        $draft->items()->create([
            'position' => 1,
            'name' => 'Гречка варёная',
            'product_id' => $this->buckwheat->id,
            'quantity' => $grams,
            'calories' => 105,
            'proteins' => 3,
            'fats' => 1,
            'carbohydrates' => 20,
            'fibers' => 0,
            'source' => MealDraftItem::SOURCE_DATABASE,
        ]);

        return $draft->fresh();
    }

    /**
     * A photo of one pixel: the model is faked, only the file reaches it.
     */
    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('meal.jpg', base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA='
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $operations
     */
    private function fakeModel(array $operations): void
    {
        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode(['operations' => $operations, 'labels' => []])]]],
        ])]);
    }
}
