<?php

namespace App\Console\Commands;

use App\Services\ChatGPTFeedback;
use Illuminate\Console\Command;

class GenerateFeedbackCommand extends Command
{
    protected $signature = 'feedback';

    protected $description = 'Generate feedback for given text using ChatGPTFeedback service';

    public function handle(ChatGPTFeedback $chat): int
    {
        $result = $chat->generateNewProductData();

        $this->info('=== Result ===');
        $this->line(is_array($result) ? print_r($result, true) : $result);

        return self::SUCCESS;
    }
}
