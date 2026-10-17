<?php

namespace Tests\Feature;

use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The bot deletes a meal by the id it showed in the stats message; the meal must
 * belong to the account the panel acts for (X-Calories-Id).
 */
class BotDeleteMealTest extends TestCase
{
    use RefreshDatabase;

    private FoodConsumption $meal;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $product = Product::create(['calories' => 100, 'proteins' => 1, 'carbohydrates' => 1, 'fats' => 1]);
        $this->meal = FoodConsumption::create([
            'user_id' => $this->owner->id, 'food_id' => $product->id, 'quantity' => 100,
            'consumed_at' => '2026-10-17', 'part_of_day' => 'morning',
        ]);
    }

    public function test_the_owner_deletes_the_meal(): void
    {
        $this->deleteAs($this->owner)->assertOk();

        $this->assertModelMissing($this->meal);
    }

    public function test_another_account_cannot_delete_the_meal(): void
    {
        $this->deleteAs(User::factory()->create())->assertForbidden();

        $this->assertModelExists($this->meal);
    }

    private function deleteAs(User $user)
    {
        return $this->deleteJson('/api/caloriesEndPoint/deleteMeal/'.$this->meal->id, [], [
            'X-Api-Key' => 'testing-bot-key',
            'X-Calories-Id' => $user->id,
        ]);
    }
}
