<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\User;
use App\Models\UserResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The insulin page: when things happened, what lifted the level, and the way of
 * life and movement the user tells on it.
 */
class InsulinDayTest extends TestCase
{
    use RefreshDatabase;

    private const DAY = '2026-10-31';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('ru');
        Carbon::setTestNow(self::DAY.' 20:00:00');
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_meal_counts_when_it_was_saved_if_that_falls_in_its_part_of_the_day(): void
    {
        $this->meal($this->bun(), 'morning', self::DAY.' 09:40:00');

        $day = $this->day()->assertOk()->json();

        $this->assertSame(580, $day['meals'][0]['minute']);
        $this->assertGreaterThan($this->levelAt($day, 570), $this->levelAt($day, 620));
    }

    public function test_a_breakfast_saved_in_the_afternoon_counts_in_the_morning(): void
    {
        $this->meal($this->bun(), 'morning', self::DAY.' 14:52:00');

        $this->assertSame(510, $this->day()->json('meals.0.minute'));
    }

    public function test_a_late_breakfast_saved_before_one_counts_when_it_was_saved(): void
    {
        $this->meal($this->bun(), 'morning', self::DAY.' 12:40:00');

        $this->assertSame(760, $this->day()->json('meals.0.minute'));
    }

    public function test_a_lunch_saved_in_the_evening_counts_when_it_was_saved(): void
    {
        $this->meal($this->bun(), 'dinner', self::DAY.' 19:28:00');

        $this->assertSame(1168, $this->day()->json('meals.0.minute'));
    }

    public function test_the_time_told_moves_all_the_foods_of_the_meal(): void
    {
        $this->meal($this->bun(), 'dinner', self::DAY.' 13:00:00');
        $this->meal($this->buckwheat(), 'dinner', self::DAY.' 13:00:00');

        $this->actingAs($this->user)
            ->putJson('/api/insulin/meal-time', ['date' => self::DAY, 'part_of_day' => 'dinner', 'time' => '14:15'])
            ->assertOk()
            ->assertJsonPath('meals.0.minute', 855)
            ->assertJsonPath('meals.0.time_told', true);
    }

    public function test_the_foods_of_a_meal_are_told_with_what_each_lifts(): void
    {
        $this->meal($this->buckwheat(), 'morning', self::DAY.' 09:00:00');
        $this->meal($this->bun(), 'morning', self::DAY.' 09:00:00');

        $items = $this->day()->json('meals.0.items');

        $this->assertSame('Булка с повидлом', $items[0]['name']);
        $this->assertSame('Гречка варёная', $items[1]['name']);
        $this->assertGreaterThan(2 * $items[1]['rise'], $items[0]['rise']);
    }

    public function test_a_late_supper_goes_on_after_midnight(): void
    {
        $this->meal($this->bun(), 'supper', '2026-10-30 23:50:00', '2026-10-30');

        $day = $this->day()->json();

        $this->assertSame([], $day['meals']);
        $this->assertGreaterThan($day['base'] + 5, $this->levelAt($day, 30));
        $this->assertEqualsWithDelta($day['base'], $this->levelAt($day, 600), 0.5);
    }

    public function test_someone_elses_meals_do_not_count(): void
    {
        $other = User::factory()->create();
        FoodConsumption::create(['user_id' => $other->id, 'food_id' => $this->bun()->id, 'quantity' => 120, 'consumed_at' => self::DAY, 'part_of_day' => 'morning']);

        $day = $this->day()->json();

        $this->assertSame([], $day['meals']);
        $this->assertSame($day['base'], $day['peak']['level']);
    }

    public function test_today_tells_the_level_now_and_a_past_day_does_not(): void
    {
        $this->assertSame(1200, $this->day()->json('now.minute'));

        $this->actingAs($this->user)->getJson('/api/insulin/2026-10-29')->assertOk()->assertJsonPath('now', null);
    }

    public function test_a_food_without_a_known_index_is_counted_and_told_as_estimated(): void
    {
        $this->meal($this->product('Новая еда', 200, 8, 3, 30, null), 'morning', self::DAY.' 09:00:00');

        $this->day()->assertJsonPath('estimated_foods', 1)->assertJsonPath('meals.0.items.0.index_known', false);
    }

    public function test_the_way_of_life_comes_from_the_calculator_until_it_is_chosen_here(): void
    {
        UserResult::create(['user_id' => $this->user->id, 'gender' => 1, 'birthYear' => 1990, 'weight' => 80, 'height' => 180, 'fat' => 0, 'activity' => 4, 'goal' => 1, 'dailyCalories' => 2500, 'checkboxActive' => 0, 'goalWeight' => 80]);
        $this->day()->assertJsonPath('lifestyle', 4)->assertJsonPath('lifestyle_known', false);

        $this->actingAs($this->user)->putJson('/api/insulin/lifestyle', ['date' => self::DAY, 'lifestyle' => 1])
            ->assertOk()
            ->assertJsonPath('lifestyle', 1)
            ->assertJsonPath('lifestyle_known', true);

        $this->assertSame(4, (int) UserResult::where('user_id', $this->user->id)->value('activity'));
    }

    public function test_a_way_of_life_out_of_the_scale_is_refused(): void
    {
        $this->actingAs($this->user)->putJson('/api/insulin/lifestyle', ['date' => self::DAY, 'lifestyle' => 9])->assertStatus(422);
    }

    public function test_a_walk_typed_on_the_page_lowers_the_meal_it_follows(): void
    {
        $this->meal($this->bun(), 'dinner', self::DAY.' 13:00:00');
        $before = $this->day()->json('peak.level');

        $this->actingAs($this->user)->postJson('/api/body/activities', [
            'date' => self::DAY, 'title' => 'Прогулка', 'minutes' => 60, 'intensity' => 'light', 'time' => '13:05',
        ])->assertOk();

        $day = $this->day()->json();

        $this->assertLessThan($before, $day['peak']['level']);
        $this->assertSame('Прогулка', $day['activities'][0]['title']);
        $this->assertSame(785, $day['activities'][0]['minute']);
        $this->assertGreaterThan(0, $day['activities'][0]['effect']);
    }

    public function test_an_activity_without_a_time_ended_when_it_was_saved(): void
    {
        $activity = Activity::create(['user_id' => $this->user->id, 'date' => self::DAY, 'title' => 'Магазин', 'minutes' => 30, 'met' => 2.5, 'calories' => 50]);
        $activity->forceFill(['created_at' => self::DAY.' 18:00:00'])->saveQuietly();

        $this->assertSame(1050, $this->day()->json('activities.0.minute'));
    }

    public function test_an_activity_needs_a_known_intensity_and_sane_minutes(): void
    {
        $this->actingAs($this->user)->postJson('/api/body/activities', ['date' => self::DAY, 'title' => 'x', 'minutes' => 0, 'intensity' => 'light'])->assertStatus(422);
        $this->actingAs($this->user)->postJson('/api/body/activities', ['date' => self::DAY, 'title' => 'x', 'minutes' => 10, 'intensity' => 'extreme'])->assertStatus(422);
    }

    public function test_the_bot_gets_the_day_with_its_chart_and_keeps_the_way_of_life(): void
    {
        $this->meal($this->bun(), 'dinner', self::DAY.' 13:00:00');
        $bot = ['X-Api-Key' => 'testing-bot-key', 'X-Calories-Id' => $this->user->id, 'X-Locale' => 'ru'];

        $day = $this->withHeaders($bot)->getJson('/api/caloriesEndPoint/insulin/'.self::DAY.'?chart=1')->assertOk()->json();

        $this->assertSame('Булка с повидлом', $day['meals'][0]['items'][0]['name']);
        $this->assertSame(IMAGETYPE_PNG, getimagesizefromstring(base64_decode($day['chart']))[2]);

        $this->withHeaders($bot)->getJson('/api/caloriesEndPoint/insulin/'.self::DAY)->assertOk()->assertJsonMissingPath('chart');
        $this->withHeaders($bot)->putJson('/api/caloriesEndPoint/insulin/lifestyle', ['date' => self::DAY, 'lifestyle' => 5])
            ->assertOk()
            ->assertJsonPath('lifestyle', 5);
        $this->assertSame(5, (int) $this->user->fresh()->lifestyle);
    }

    public function test_the_day_tells_sugar_with_insulin_and_what_changed_each_meal(): void
    {
        $this->meal($this->bun(), 'dinner', self::DAY.' 13:00:00');
        $this->meal($this->product('Курица запечённая', 210, 25, 12, 0, 0, 0), 'dinner', self::DAY.' 13:00:00');

        $day = $this->day()->assertOk()->json();
        $meal = $day['meals'][0];

        $this->assertEquals(5.0, $day['glucose']['base']);
        $this->assertGreaterThan(6.5, $meal['glucose_peak']);
        $this->assertEqualsWithDelta($day['glucose']['peak']['level'], max(array_column($day['points'], 'glucose')), 0.05);
        $this->assertSame(round($day['glucose']['peak']['level'], 1), $day['glucose']['peak']['level']);
        $this->assertSame('Булка с повидлом', $meal['items'][0]['name']);
        $this->assertEqualsWithDelta($meal['glucose_rise'], array_sum(array_column($meal['items'], 'glucose')), 0.11);
        $this->assertStringStartsWith('белок −', collect($meal['factors'])->firstWhere('key', 'proteins')['label']);
    }

    public function test_the_summary_counts_protein_fibre_and_the_ultra_processed_share(): void
    {
        $this->meal($this->product('Хлеб тостовый', 260, 8, 3, 48, 75, 5, nova: 4, fibers: 3), 'morning', self::DAY.' 09:00:00');
        $this->meal($this->product('Чечевица варёная', 116, 9, 0.4, 20, 30, 1.8, nova: 1, fibers: 8), 'dinner', self::DAY.' 13:00:00');
        UserResult::create(['user_id' => $this->user->id, 'gender' => 1, 'birthYear' => 1990, 'weight' => 80, 'height' => 180, 'fat' => 0, 'activity' => 3, 'goal' => 1, 'dailyCalories' => 2500, 'checkboxActive' => 0, 'goalWeight' => 80]);

        $summary = $this->day()->json('summary');

        // 120 g of toast and 200 g of lentils.
        $this->assertEqualsWithDelta(9.6 + 18, $summary['proteins'], 0.1);
        $this->assertEqualsWithDelta(3.6 + 16, $summary['fibers'], 0.1);
        $this->assertSame(66, $this->day()->json('summary.proteins_target'));
        $this->assertSame((int) round(312 / (312 + 232) * 100), $summary['ultra_processed_share']);
        $this->assertSame(25, $summary['fibers_target']);
    }

    public function test_a_sweet_supper_without_a_walk_after_it_gets_the_tips_of_a_walk_and_of_the_dip_to_come(): void
    {
        $this->meal($this->bun(), 'supper', self::DAY.' 18:30:00');

        $tips = $this->day()->json('tips');

        $this->assertStringContainsString('Прогулка 10 минут', $tips[0]);
        $this->assertStringContainsString('опустится ниже обычного', $tips[1]);
    }

    public function test_a_short_night_and_a_walk_tell_their_share_of_the_day(): void
    {
        $this->meal($this->bun(), 'dinner', self::DAY.' 13:00:00');
        Activity::create(['user_id' => $this->user->id, 'date' => self::DAY, 'started_at' => self::DAY.' 02:00:00', 'type' => 'sleep', 'title' => 'Сон', 'minutes' => 300, 'met' => 1.0, 'calories' => 0]);
        Activity::create(['user_id' => $this->user->id, 'date' => self::DAY, 'started_at' => self::DAY.' 13:10:00', 'type' => 'walk', 'title' => 'Прогулка', 'minutes' => 20, 'met' => 3.0, 'calories' => 50]);

        $day = $this->day()->json();
        [$sleep, $walk] = $day['activities'];

        $this->assertTrue($sleep['sleep']);
        $this->assertLessThan(0, $sleep['glucose_effect']);
        $this->assertGreaterThan(5, $walk['glucose_effect']);
        $this->assertEquals(5.0, $day['summary']['sleep_hours']);
        $this->assertSame(20, $day['summary']['moved_after_meals_minutes']);
        $this->assertNotNull(collect($day['meals'][0]['factors'])->firstWhere('key', 'walk'));
    }

    public function test_a_shop_that_ended_as_the_supper_began_counts_before_it_not_after_it(): void
    {
        $this->meal($this->bun(), 'supper', self::DAY.' 19:28:00');
        Activity::create(['user_id' => $this->user->id, 'date' => self::DAY, 'started_at' => self::DAY.' 17:29:00', 'title' => 'Ходил в магазин', 'minutes' => 120, 'met' => 2.5, 'calories' => 300]);

        $day = $this->day()->json();
        $meal = $day['meals'][0];

        $this->assertSame(0, $day['summary']['moved_after_meals_minutes']);
        $this->assertNull(collect($meal['factors'])->firstWhere('key', 'walk'));
        $this->assertSame('движение до еды: инсулин −12%', collect($meal['factors'])->firstWhere('key', 'exercise')['label']);
        $this->assertGreaterThanOrEqual(10, $day['activities'][0]['effect']);
        $this->assertGreaterThan(0, $day['activities'][0]['glucose_effect']);
    }

    public function test_a_walk_counts_after_the_meal_for_the_minutes_it_still_counts(): void
    {
        $this->meal($this->bun(), 'dinner', self::DAY.' 13:00:00');
        Activity::create(['user_id' => $this->user->id, 'date' => self::DAY, 'started_at' => self::DAY.' 15:30:00', 'type' => 'walk', 'title' => 'Прогулка', 'minutes' => 60, 'met' => 3.0, 'calories' => 150]);

        $this->assertSame(30, $this->day()->json('summary.moved_after_meals_minutes'));
    }

    public function test_chicken_lifts_the_total_of_insulin_and_not_the_level_that_tells_the_zone(): void
    {
        $this->meal($this->buckwheat(), 'dinner', self::DAY.' 13:00:00');
        $thigh = $this->product('Курица запечённая', 210, 26, 11, 0, 0, 0);
        $this->meal($thigh, 'dinner', self::DAY.' 13:00:00');
        $this->meal($thigh, 'dinner', self::DAY.' 13:00:00');

        $day = $this->day()->assertOk()->json();
        $meal = $day['meals'][0];
        $chicken = collect($meal['items'])->firstWhere('name', 'Курица запечённая');

        $this->assertLessThan($day['total_peak']['level'], $day['peak']['level']);
        $this->assertLessThan($day['zones']['high'], $day['peak']['level']);
        $this->assertGreaterThan(0, $meal['rise_of_protein']);
        $this->assertSame(0, (int) $chicken['rise']);
        $this->assertGreaterThan(5, $chicken['rise_of_protein']);
        $this->assertGreaterThanOrEqual($day['points'][170]['level'], $day['points'][170]['total']);
        $this->assertNotEmpty(collect($day['tips'])->filter(static fn (string $tip) => str_contains($tip, 'от белка и жира')));
    }

    public function test_the_fibre_of_grains_counts_for_the_day_and_not_for_the_rise(): void
    {
        $this->meal($this->product('Гречка варёная', 105, 3.6, 1, 20, 50, 0.5, fibers: 2.7), 'dinner', self::DAY.' 13:00:00');

        $meal = $this->day()->json('meals.0');

        $this->assertEqualsWithDelta(5.4, $meal['fibers'], 0.05);
        $this->assertNull(collect($meal['factors'])->firstWhere('key', 'fibers'));
        $this->assertEqualsWithDelta(5.4, $this->day()->json('summary.fibers'), 0.05);
    }

    public function test_the_bot_needs_its_key(): void
    {
        $this->getJson('/api/caloriesEndPoint/insulin/'.self::DAY, ['X-Calories-Id' => $this->user->id])->assertUnauthorized();
    }

    public function test_the_page_needs_an_account(): void
    {
        $this->getJson('/api/insulin/'.self::DAY)->assertUnauthorized();
    }

    private function day()
    {
        return $this->actingAs($this->user)->getJson('/api/insulin/'.self::DAY);
    }

    /**
     * @param  array<string, mixed>  $day
     */
    private function levelAt(array $day, int $minute): float
    {
        return (float) collect($day['points'])->firstWhere('minute', $minute)['level'];
    }

    private function bun(): Product
    {
        return $this->product('Булка с повидлом', 340, 7, 8, 60, 85, 25);
    }

    private function buckwheat(): Product
    {
        return $this->product('Гречка варёная', 105, 3, 1, 20, 50, 0.5);
    }

    private function product(string $name, float $calories, float $proteins, float $fats, float $carbohydrates, ?int $index, ?float $sugars = null, ?int $nova = null, float $fibers = 0): Product
    {
        $product = Product::create([
            'calories' => $calories, 'proteins' => $proteins, 'fats' => $fats, 'carbohydrates' => $carbohydrates, 'fibers' => $fibers,
            'glycemic_index' => $index, 'sugars' => $sugars, 'nova' => $nova,
        ]);
        ProductTranslation::create(['product_id' => $product->id, 'locale' => 'ru', 'name' => $name, 'active' => 1, 'verified' => 0]);

        return $product;
    }

    private function meal(Product $product, string $part, string $savedAt, string $date = self::DAY): void
    {
        $meal = FoodConsumption::create(['user_id' => $this->user->id, 'food_id' => $product->id, 'quantity' => $product->calories > 200 ? 120 : 200, 'consumed_at' => $date, 'part_of_day' => $part]);
        $meal->forceFill(['created_at' => $savedAt])->saveQuietly();
    }
}
