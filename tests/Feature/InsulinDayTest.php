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
        $this->meal($this->bun(), 'morning', self::DAY.' 16:30:00');

        $this->assertSame(510, $this->day()->json('meals.0.minute'));
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

    private function product(string $name, float $calories, float $proteins, float $fats, float $carbohydrates, ?int $index, ?float $sugars = null): Product
    {
        $product = Product::create([
            'calories' => $calories, 'proteins' => $proteins, 'fats' => $fats, 'carbohydrates' => $carbohydrates, 'fibers' => 0,
            'glycemic_index' => $index, 'sugars' => $sugars,
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
