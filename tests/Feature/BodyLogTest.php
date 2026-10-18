<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\BodyWeight;
use App\Models\User;
use App\Services\BodyLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The body weight and the activities a user tells go to the diary of the day,
 * through the same messages as meals.
 */
class BodyLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->user = User::factory()->create();
    }

    public function test_a_weight_said_plainly_is_read_without_the_model(): void
    {
        $this->assertSame(82.4, BodyLog::weightIn('вешу 82,4'));
        $this->assertSame(82.4, BodyLog::weightIn('Вес утром 82.4 кг'));
        $this->assertSame(79.0, BodyLog::weightIn('вага 79'));
        $this->assertSame(91.5, BodyLog::weightIn('91,5 кг'));
        $this->assertNull(BodyLog::weightIn('2 250'));
        $this->assertNull(BodyLog::weightIn('гречка 200'));
        $this->assertNull(BodyLog::weightIn('вешу 8'));
    }

    public function test_a_weight_message_is_kept_at_once_and_replaces_the_one_of_the_day(): void
    {
        $this->collect(['text' => 'вешу 82,4'])->assertOk()
            ->assertJsonPath('recorded.weight', 82.4)
            ->assertJsonPath('inputs', []);
        $this->collect(['text' => 'вес 82,1 кг'])->assertOk();

        $this->assertSame(82.1, BodyWeight::where('user_id', $this->user->id)->sole()->weight);
        Http::assertNothingSent();
    }

    public function test_an_activity_burns_by_the_weight_of_the_day_above_rest(): void
    {
        BodyWeight::create(['user_id' => $this->user->id, 'date' => today()->subDays(3), 'weight' => 80]);

        $activity = app(BodyLog::class)->recordActivity($this->user->id, today()->toDateString(), 'Бег 5 км', 30, 9.8);

        // (9.8 − 1) × 80 kg × 0.5 h
        $this->assertSame(352, $activity->calories);
    }

    public function test_the_calculation_keeps_the_weight_and_the_activities_it_was_told(): void
    {
        $this->collect(['kind' => 'voice', 'text' => 'утром весил 83 и пробежал пять километров за полчаса'])->assertOk();
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'operations' => [],
            'labels' => [],
            'weight_kg' => 83,
            'activities' => [['title' => 'Бег 5 км', 'minutes' => 30, 'met' => 9.8]],
        ])]]]])]);

        $this->bot()->postJson('/api/caloriesEndPoint/draft/calculate')->assertStatus(202)
            ->assertJsonPath('calculation.status', 'done')
            ->assertJsonPath('calculation.understood', true)
            ->assertJsonPath('calculation.recorded.weight', 83)
            ->assertJsonPath('calculation.recorded.activities.0.title', 'Бег 5 км')
            ->assertJsonPath('calculation.recorded.activities.0.calories', 365)
            ->assertJsonPath('draft', null);

        $this->assertSame(83.0, BodyWeight::where('user_id', $this->user->id)->sole()->weight);
        $this->assertSame(1, Activity::where('user_id', $this->user->id)->count());
        Http::assertSent(fn (Request $request) => str_contains($request['messages'][0]['content'][0]['text'], 'Compendium of Physical Activities'));
    }

    public function test_an_activity_the_model_cannot_have_meant_is_not_kept(): void
    {
        $this->collect(['text' => 'плавал'])->assertOk();
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'operations' => [],
            'labels' => [],
            'weight_kg' => 8,
            'activities' => [['title' => 'Плавание', 'minutes' => 0, 'met' => 6]],
        ])]]]])]);

        $this->bot()->postJson('/api/caloriesEndPoint/draft/calculate')->assertStatus(202)
            ->assertJsonPath('calculation.understood', false);

        $this->assertSame(0, Activity::count() + BodyWeight::count());
    }

    public function test_the_diary_page_shows_the_day_and_takes_a_typed_weight(): void
    {
        $date = today()->toDateString();
        app(BodyLog::class)->recordActivity($this->user->id, $date, 'Ходьба', 60, 3.5);

        $this->actingAs($this->user)->getJson("/api/body/{$date}")->assertOk()
            ->assertJsonPath('weight', null)
            ->assertJsonPath('activities.0.title', 'Ходьба')
            ->assertJsonPath('calories', 175);

        $this->actingAs($this->user)->postJson('/api/body/weight', ['date' => $date, 'weight' => 81.3])->assertOk()
            ->assertJsonPath('weight', 81.3);

        $this->actingAs($this->user)->postJson('/api/body/weight', ['date' => $date, 'weight' => 5])->assertUnprocessable();
    }

    public function test_an_activity_is_removed_only_by_its_user(): void
    {
        $activity = app(BodyLog::class)->recordActivity($this->user->id, today()->toDateString(), 'Ходьба', 60, 3.5);

        $this->actingAs(User::factory()->create())->deleteJson("/api/body/activities/{$activity->id}")->assertNotFound();
        $this->actingAs($this->user)->deleteJson("/api/body/activities/{$activity->id}")->assertOk()
            ->assertJsonPath('activities', []);
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
}
