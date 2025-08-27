<?php

namespace App\Jobs;

use App\Models\DietFeedback;
use App\Models\FoodConsumption;
use App\Models\UserResult;
use App\Services\ChatGPTFeedback;
use App\Support\Formatters\FeedbackPromptFormatter;
use App\Support\Formatters\MealsFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class GenerateDietFeedbackJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 600;

    public int $userId;

    public string $date;

    public ?string $partOfDay;

    public string $locale;

    public string $signature;

    public int $tries = 5;

    public int $timeout = 60;

    public $backoff = [5, 10, 20, 30];

    public function __construct(int $userId, string $date, ?string $partOfDay, string $locale, string $signature)
    {
        $this->userId = $userId;
        $this->date = $date;
        $this->partOfDay = $partOfDay;
        $this->locale = $locale;
        $this->signature = $signature;
        $this->onQueue('feedback');
    }

    public function uniqueId(): string
    {
        return "feedback|{$this->userId}|{$this->date}|".($this->partOfDay ?? 'all')."|{$this->signature}";
    }

    public function middleware(): array
    {
        return [new RateLimited('openai-feedback')];
    }

    public function handle(ChatGPTFeedback $ai): void
    {
        $rec = DietFeedback::findBySignature($this->userId, $this->date, $this->partOfDay, $this->signature);

        if ($rec && $rec->status === 'ready' && $rec->feedback_text) {
            return;
        }

        $meals = FoodConsumption::getMealsWithCurrentDate(
            $this->date, $this->userId, $this->locale, $this->partOfDay
        );

        $nameGramsList = MealsFormatter::toNameGramsList($meals);

        $userResult = UserResult::where('user_id', $this->userId)->first();
        $userInfo = FeedbackPromptFormatter::buildUserInfo($userResult, $this->locale);
        $partOfDayInfo = FeedbackPromptFormatter::buildPartOfDayInfo($this->partOfDay, $this->locale);
        $mealsList = FeedbackPromptFormatter::buildMealsList($nameGramsList, $this->locale);
        $prompt = FeedbackPromptFormatter::buildPrompt($userInfo, $partOfDayInfo, $mealsList, $this->locale);
        $res = $ai->generateNewProductData($prompt);

        DietFeedback::upsertReadyBySignature(
            userId: $this->userId,
            date: $this->date,
            partOfDay: $this->partOfDay,
            signature: $this->signature,
            feedback: $res
        );
    }

    public function failed(\Throwable $e): void
    {
        DietFeedback::updateStatusBySignature(
            userId: $this->userId,
            date: $this->date,
            partOfDay: $this->partOfDay,
            signature: $this->signature,
            status: 'failed'
        );
    }
}
