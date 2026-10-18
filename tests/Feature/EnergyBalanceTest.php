<?php

namespace Tests\Feature;

use App\Models\BodyWeight;
use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\User;
use App\Models\UserResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The real daily expenditure comes from what the diary says was eaten and how
 * the weight went, once there are enough days of both.
 */
class EnergyBalanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $food;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['calories_limit' => 1800]);
        $this->food = Product::create(['calories' => 220, 'proteins' => 10, 'fats' => 8, 'carbohydrates' => 25, 'fibers' => 0]);
    }

    public function test_eating_2200_and_losing_half_a_kilogram_a_week_means_spending_2750(): void
    {
        UserResult::create(['user_id' => $this->user->id, 'gender' => 'male', 'birthYear' => 1990, 'weight' => 80, 'height' => 180, 'goalWeight' => 75, 'fat' => 0, 'activity' => 1, 'goal' => 1, 'dailyCalories' => 1800, 'checkboxActive' => 0]);

        for ($day = 20; $day >= 1; $day--) {
            $this->eat(today()->subDays($day), 1000);
        }
        // A day logged in part does not count.
        $this->eat(today(), 200);
        foreach ([21 => 80.0, 14 => 79.5, 7 => 79.0, 0 => 78.5] as $daysAgo => $weight) {
            BodyWeight::create(['user_id' => $this->user->id, 'date' => today()->subDays($daysAgo), 'weight' => $weight]);
        }

        $this->actingAs($this->user)->getJson('/api/energy')->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('expenditure', 2750)
            ->assertJsonPath('intake', 2200)
            ->assertJsonPath('weight_change_per_week', -0.5)
            ->assertJsonPath('goal', 'lose')
            ->assertJsonPath('norm', 2340)
            ->assertJsonPath('logged_days', 20);

        $this->actingAs($this->user)->postJson('/api/energy/apply')->assertOk()
            ->assertJsonPath('calories_limit', 2340);
        $this->assertSame(2340, $this->user->fresh()->calories_limit);
    }

    public function test_without_enough_days_it_says_what_the_diary_still_needs(): void
    {
        for ($day = 5; $day >= 1; $day--) {
            $this->eat(today()->subDays($day), 1000);
        }
        BodyWeight::create(['user_id' => $this->user->id, 'date' => today(), 'weight' => 80]);

        $this->actingAs($this->user)->getJson('/api/energy')->assertOk()
            ->assertJsonPath('status', 'collecting')
            ->assertJsonPath('logged_days', 5)
            ->assertJsonPath('weights', 1)
            ->assertJsonPath('needed.logged_days', 10);

        $this->actingAs($this->user)->postJson('/api/energy/apply')->assertUnprocessable();
        $this->assertSame(1800, $this->user->fresh()->calories_limit);
    }

    public function test_an_intake_and_a_weight_no_body_could_have_are_called_unclear(): void
    {
        for ($day = 14; $day >= 1; $day--) {
            $this->eat(today()->subDays($day), 400);
        }
        foreach ([14 => 80.0, 7 => 80.0, 0 => 80.0] as $daysAgo => $weight) {
            BodyWeight::create(['user_id' => $this->user->id, 'date' => today()->subDays($daysAgo), 'weight' => $weight]);
        }

        // 880 kcal a day with the weight standing: meals are missing from the diary.
        $this->actingAs($this->user)->getJson('/api/energy')->assertOk()
            ->assertJsonPath('status', 'unclear')
            ->assertJsonPath('intake', 880);
    }

    private function eat(\Illuminate\Support\Carbon $day, int $grams): void
    {
        FoodConsumption::create(['user_id' => $this->user->id, 'food_id' => $this->food->id, 'quantity' => $grams, 'part_of_day' => 'dinner', 'consumed_at' => $day->copy()->setTime(13, 0)]);
    }
}
