<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\BodyWeight;
use App\Models\User;
use App\Services\BodyLog;
use App\Services\BotPanelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
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
        $this->withHeader('Accept-Language', 'ru');
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

    public function test_the_time_of_an_activity_is_kept_when_it_was_told_and_a_wrong_one_is_not(): void
    {
        Carbon::setTestNow(today()->setTime(19, 0));
        $this->collect(['text' => 'сходил в магазин в 6 вечера и гулял'])->assertOk();
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'operations' => [],
            'labels' => [],
            'weight_kg' => null,
            'activities' => [
                ['title' => 'Магазин', 'minutes' => 30, 'met' => 2.5, 'time' => '18:00'],
                ['title' => 'Прогулка', 'minutes' => 30, 'met' => 2.5, 'time' => 'вечером'],
            ],
        ])]]]])]);

        $this->bot()->postJson('/api/caloriesEndPoint/draft/calculate')->assertStatus(202);

        $this->assertSame('18:00', Activity::where('title', 'Магазин')->sole()->started_at->format('H:i'));
        // A time that is no time: the walk ended when it was told.
        $this->assertSame('18:30', Activity::where('title', 'Прогулка')->sole()->started_at->format('H:i'));
        Carbon::setTestNow();
    }

    public function test_a_kind_tapped_on_the_page_keeps_its_met_and_ended_when_it_was_tapped(): void
    {
        Carbon::setTestNow('2026-11-01 19:00:00');

        $this->actingAs($this->user)->postJson('/api/body/activities', ['date' => '2026-11-01', 'type' => 'walk_brisk', 'minutes' => 30])
            ->assertOk()
            ->assertJsonPath('activity.title', 'Быстрая ходьба')
            ->assertJsonPath('activity.type', 'walk_brisk')
            ->assertJsonPath('activity.time', '18:30');

        $this->assertSame(4.8, Activity::sole()->met);
        Carbon::setTestNow();
    }

    public function test_a_kind_out_of_the_catalogue_or_too_long_is_refused(): void
    {
        $this->actingAs($this->user)->postJson('/api/body/activities', ['date' => '2026-11-01', 'type' => 'flying', 'minutes' => 30])->assertUnprocessable();
        $this->actingAs($this->user)->postJson('/api/body/activities', ['date' => '2026-11-01', 'type' => 'walk', 'minutes' => 700])->assertUnprocessable();
        $this->actingAs($this->user)->postJson('/api/body/activities', ['date' => '2026-11-01', 'type' => 'sleep', 'minutes' => 700])->assertOk();
    }

    public function test_movement_said_in_words_is_recorded_at_once(): void
    {
        Carbon::setTestNow('2026-11-01 19:00:00');
        $this->mock(BotPanelService::class, fn ($mock) => $mock->shouldReceive('canTranscribeAudio')->once()->andReturn(true));
        $this->fakeActivities([
            ['type' => 'walk', 'title' => 'Прогулка', 'minutes' => 60, 'met' => 3.0, 'time' => null],
            ['type' => 'run', 'title' => 'Бег 5 км', 'minutes' => 30, 'met' => 1.0, 'time' => '07:00'],
        ]);

        $this->actingAs($this->user)->postJson('/api/body/activities/say', ['text' => 'час гулял, а утром в 7 пробежал 5 км'])
            ->assertOk()
            ->assertJsonPath('understood', true)
            ->assertJsonPath('recorded.0.title', 'Прогулка')
            ->assertJsonPath('recorded.0.time', '18:00')
            ->assertJsonPath('recorded.1.time', '07:00');

        // The MET of a kind of the catalogue is its own, whatever the model said.
        $this->assertSame(8.0, Activity::where('type', 'run')->sole()->met);
        Http::assertSent(fn (Request $request) => str_contains($request['messages'][0]['content'], 'Now it is 19:00'));
        Carbon::setTestNow();
    }

    public function test_words_without_movement_record_nothing(): void
    {
        $this->fakeActivities([]);

        $this->bot()->postJson('/api/caloriesEndPoint/body/activities/say', ['text' => 'съел банан'])
            ->assertOk()
            ->assertJsonPath('understood', false)
            ->assertJsonPath('recorded', []);

        $this->assertSame(0, Activity::count());
    }

    public function test_last_nights_sleep_told_in_the_morning_belongs_to_this_morning(): void
    {
        Carbon::setTestNow('2026-11-01 08:30:00');
        $this->fakeActivities([['type' => 'sleep', 'title' => 'Сон', 'minutes' => 480, 'met' => 1.0, 'time' => '23:00']]);

        $this->bot()->postJson('/api/caloriesEndPoint/body/activities/say', ['text' => 'спал с 23 до 7'])->assertOk();

        $sleep = Activity::sole();
        $this->assertSame('2026-10-31 23:00', $sleep->started_at->format('Y-m-d H:i'));
        $this->assertSame('2026-11-01', $sleep->date->toDateString());
        $this->assertSame(0, $sleep->calories);
        Carbon::setTestNow();
    }

    public function test_the_bot_records_a_tapped_kind_and_removes_it(): void
    {
        $this->bot()->getJson('/api/caloriesEndPoint/activity-types')->assertOk()
            ->assertJsonPath('types.0.type', 'walk')
            ->assertJsonPath('types.0.title', 'Прогулка');

        $id = $this->bot()->postJson('/api/caloriesEndPoint/body/activities', ['date' => today()->toDateString(), 'type' => 'gym', 'minutes' => 60])
            ->assertOk()
            ->assertJsonPath('activity.title', 'Зал, силовая')
            ->json('activity.id');

        $this->bot()->deleteJson("/api/caloriesEndPoint/body/activities/{$id}")->assertOk();
        $this->assertSame(0, Activity::count());
    }

    public function test_the_time_and_minutes_of_an_activity_are_corrected_on_the_page(): void
    {
        $activity = app(BodyLog::class)->recordActivity($this->user->id, '2026-10-30', 'Прогулка', 60, 3.0, null, 'walk');

        $this->actingAs($this->user)->patchJson("/api/body/activities/{$activity->id}", ['time' => '17:15', 'minutes' => 30])
            ->assertOk()
            ->assertJsonPath('activity.time', '17:15')
            ->assertJsonPath('activity.minutes', 30);

        $this->assertSame((int) round($activity->calories / 2), $activity->fresh()->calories);
        $this->actingAs(User::factory()->create())->patchJson("/api/body/activities/{$activity->id}", ['time' => '10:00'])->assertNotFound();
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
     * @param  array<int, array<string, mixed>>  $activities
     */
    private function fakeActivities(array $activities): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode(['activities' => $activities])]]]])]);
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
