<?php

namespace App\Jobs;

use App\Services\MealDraftService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Calculates the meal draft from the inputs the user sent for it
 * (MealDraftService::runCalculation). Queued: the model reads several photos
 * for half a minute and more, longer than a web request may wait. The site and
 * the bot ask for the outcome with MealDraftService::calculation.
 */
class CalculateMealDraftJob implements ShouldQueue
{
    use Queueable;

    /**
     * One attempt: a second would send the photos to the model again, and the
     * user sees the failure and can calculate again.
     */
    public int $tries = 1;

    public int $timeout = 180;

    /**
     * @param  array<int, int>  $inputIds
     */
    public function __construct(public int $userId, public string $locale, public array $inputIds)
    {
        $this->onQueue('AI');
    }

    public function handle(MealDraftService $drafts): void
    {
        $drafts->runCalculation($this->userId, $this->locale, $this->inputIds);
    }
}
