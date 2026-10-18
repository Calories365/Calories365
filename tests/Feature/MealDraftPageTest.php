<?php

namespace Tests\Feature;

use App\Models\MealDraft;
use App\Models\MealDraftItem;
use App\Models\Product;
use App\Models\User;
use App\Services\AudioConversionService;
use App\Services\BotPanelService;
use App\Services\ProductCatalog;
use App\Services\SpeechToTextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The "Приём пищи" page of the site and the Mini App: the same meal draft as the
 * bot, with voice, text and photo input and an editor of its items.
 */
class MealDraftPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $buckwheat;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->user = User::factory()->create();
        $this->buckwheat = Product::create(['calories' => 105, 'proteins' => 3, 'fats' => 1, 'carbohydrates' => 20, 'fibers' => 0]);

        $this->partialMock(ProductCatalog::class, fn ($mock) => $mock->shouldReceive('find')->andReturnUsing(fn (array $names) => array_map(
            fn (string $name) => ['id' => 1, 'product_id' => $this->buckwheat->id, 'locale' => 'ru', 'name' => $name, 'user_id' => null],
            $names
        )));
    }

    public function test_a_typed_meal_goes_to_the_draft_within_the_spam_guard(): void
    {
        $this->guard(true);
        $this->fakeModel('Гречка варёная', 200);

        $this->page()->postJson('/api/draft/input', ['text' => 'гречка 200 грамм'])->assertOk()
            ->assertJsonPath('draft.items.0.name', 'Гречка варёная')
            ->assertJsonPath('draft.items.0.grams', 200);
    }

    public function test_too_many_inputs_are_refused(): void
    {
        $this->guard(false);

        $this->page()->postJson('/api/draft/input', ['text' => 'гречка'])->assertStatus(429)->assertJsonPath('message', 'voice_rate_limited');
    }

    public function test_a_short_edit_is_not_counted(): void
    {
        $this->draftWith(200);
        $this->mock(BotPanelService::class, fn ($mock) => $mock->shouldNotReceive('canTranscribeAudio'));

        $this->page()->postJson('/api/draft/input', ['text' => '1 250'])->assertOk()->assertJsonPath('draft.items.0.grams', 250);
    }

    public function test_a_voice_recording_is_transcribed_into_the_draft(): void
    {
        $this->guard(true);
        $this->fakeModel('Гречка варёная', 180);
        // gpt-transcribe takes the webm a browser records: converting it is not needed.
        $this->mock(AudioConversionService::class, fn ($mock) => $mock->shouldNotReceive('convertToMp3'));
        $this->mock(SpeechToTextService::class, fn ($mock) => $mock->shouldReceive('transcribe')->once()->andReturn('гречки грамм 180'));

        $this->page()->post('/api/draft/input', ['audio' => UploadedFile::fake()->create('record.webm', 10, 'audio/webm')], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('text', 'гречки грамм 180')
            ->assertJsonPath('draft.items.0.grams', 180);
    }

    public function test_grams_change_the_portion_and_typed_facts_make_the_item_the_users_own(): void
    {
        $item = $this->draftWith(200)->items->first();

        $this->page()->patchJson("/api/draft/items/{$item->id}", ['grams' => 150])->assertOk()
            ->assertJsonPath('draft.items.0.grams', 150)
            ->assertJsonPath('draft.items.0.edited', false);

        $this->page()->patchJson("/api/draft/items/{$item->id}", ['calories' => 120])->assertOk()
            ->assertJsonPath('draft.items.0.per_100g.calories', 120)
            ->assertJsonPath('draft.items.0.edited', true);
    }

    public function test_an_item_of_another_user_is_not_found(): void
    {
        $item = $this->draftWith(200)->items->first();

        $this->actingAs(User::factory()->create())->patchJson("/api/draft/items/{$item->id}", ['grams' => 150])->assertNotFound();
        $this->actingAs(User::factory()->create())->deleteJson("/api/draft/items/{$item->id}")->assertNotFound();
    }

    public function test_removing_the_last_item_closes_the_draft(): void
    {
        $item = $this->draftWith(200)->items->first();

        $this->page()->deleteJson("/api/draft/items/{$item->id}")->assertOk()->assertJsonPath('draft', null);

        $this->assertSame(0, MealDraft::count());
    }

    public function test_the_page_shows_the_draft_the_bot_started(): void
    {
        $this->draftWith(200);

        $this->page()->getJson('/api/draft')->assertOk()->assertJsonPath('draft.items.0.name', 'Гречка варёная');
    }

    public function test_the_draft_is_shown_only_to_a_signed_in_user(): void
    {
        $this->getJson('/api/draft')->assertUnauthorized();
    }

    private function page(): static
    {
        return $this->actingAs($this->user);
    }

    private function guard(bool $allowed): void
    {
        $this->mock(BotPanelService::class, fn ($mock) => $mock->shouldReceive('canTranscribeAudio')->once()->with($this->user->id)->andReturn($allowed));
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

    private function fakeModel(string $name, float $grams): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'operations' => [['action' => 'add', 'item' => null, 'name' => $name, 'grams' => $grams, 'label' => null]],
            'labels' => [],
        ])]]]])]);
    }
}
