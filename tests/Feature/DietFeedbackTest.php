<?php

namespace Tests\Feature;

use App\Jobs\GenerateDietFeedbackJob;
use App\Models\DietFeedback;
use App\Models\FoodConsumption;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\User;
use App\Services\ChatGPTFeedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DietFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private const DATE = '2026-10-17';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        $this->user = User::factory()->create();

        $product = Product::create([
            'calories' => 330, 'proteins' => 12, 'carbohydrates' => 62, 'fats' => 3, 'fibers' => 10,
        ]);
        ProductTranslation::create([
            'product_id' => $product->id, 'locale' => 'en', 'name' => 'Buckwheat', 'active' => 1, 'verified' => 1,
        ]);
        FoodConsumption::create([
            'user_id' => $this->user->id, 'food_id' => $product->id, 'quantity' => 200,
            'consumed_at' => self::DATE, 'part_of_day' => 'morning',
        ]);
    }

    public function test_a_failed_openai_call_does_not_store_feedback_as_ready(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'down'], 500)]);
        $signature = $this->pendingSignature();

        try {
            $this->runJob($signature);
            $this->fail('The job must fail so the queue retries it.');
        } catch (\Throwable) {
        }

        $record = DietFeedback::findBySignature($this->user->id, self::DATE, null, $signature);
        $this->assertSame('pending', $record->status);
        $this->assertNull($record->feedback_text);
    }

    public function test_the_last_failed_attempt_marks_feedback_as_failed(): void
    {
        $signature = $this->pendingSignature();

        $this->makeJob($signature)->failed(new \RuntimeException('down'));

        $this->assertSame('failed', DietFeedback::findBySignature($this->user->id, self::DATE, null, $signature)->status);
    }

    public function test_a_successful_call_stores_the_feedback_as_ready(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'Add protein.']]]])]);
        $signature = $this->pendingSignature();

        $this->runJob($signature);

        $record = DietFeedback::findBySignature($this->user->id, self::DATE, null, $signature);
        $this->assertSame('ready', $record->status);
        $this->assertSame('Add protein.', $record->feedback_text);
    }

    public function test_the_job_works_in_the_locale_of_the_request(): void
    {
        $locales = [];
        Http::fake(function (Request $request) use (&$locales) {
            $locales[] = app()->getLocale();

            return Http::response(['choices' => [['message' => ['content' => 'ok']]]]);
        });
        app()->setLocale('ua');

        $this->runJob($this->pendingSignature(), 'en');

        $this->assertSame(['en'], $locales);
    }

    public function test_feedback_that_failed_before_is_generated_again(): void
    {
        Queue::fake();
        $signature = $this->pendingSignature();
        DietFeedback::updateStatusBySignature($this->user->id, self::DATE, null, $signature, 'failed');

        $this->actingAs($this->user)
            ->getJson('/api/getFeedback?date='.self::DATE)
            ->assertStatus(202);

        Queue::assertPushed(GenerateDietFeedbackJob::class);
        $this->assertSame('pending', DietFeedback::findBySignature($this->user->id, self::DATE, null, $signature)->status);
    }

    public function test_feedback_does_not_require_premium(): void
    {
        Queue::fake();
        $this->user->forceFill(['premium_until' => null])->save();

        $this->actingAs($this->user)
            ->getJson('/api/getFeedback?date='.self::DATE)
            ->assertStatus(202);

        Queue::assertPushed(GenerateDietFeedbackJob::class);
    }

    private function pendingSignature(): string
    {
        $meals = FoodConsumption::getMealsWithCurrentDate(self::DATE, $this->user->id, 'en');
        $signature = DietFeedback::buildMealsSignature($meals);
        DietFeedback::insertPendingIfMissing($this->user->id, self::DATE, null, $signature);

        return $signature;
    }

    private function makeJob(string $signature, string $locale = 'en'): GenerateDietFeedbackJob
    {
        return new GenerateDietFeedbackJob($this->user->id, self::DATE, null, $locale, $signature);
    }

    private function runJob(string $signature, string $locale = 'en'): void
    {
        $this->makeJob($signature, $locale)->handle(app(ChatGPTFeedback::class));
    }
}
