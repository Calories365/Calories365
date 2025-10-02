<?php

namespace App\Jobs;

use App\Services\SpeechToTextService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateProductDataJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 120;

    public $backoff = [5, 10, 20, 30];

    public int $uniqueFor = 600; // 10 minutes

    public function middleware(): array
    {
        return [new RateLimited('openai-feedback')];
    }

    public function uniqueId(): string
    {
        return 'voice-gen|'.$this->rid;
    }

    public function __construct(
        public int $userId,
        public string $rid,
        public string $productName,
        public string $locale
    ) {
        $this->onQueue('AI');
    }

    public function handle(SpeechToTextService $stt): void
    {
        App::setLocale($this->locale);

        $cacheKey = 'voice:job:'.$this->rid;

        try {
            $generatedData = $stt->generateNewProductData($this->productName);

            if (is_array($generatedData) && isset($generatedData['error'])) {
                Cache::put($cacheKey, [
                    'status' => 'failed',
                    'message' => 'Ошибка при генерации данных: '.$generatedData['error'],
                ], now()->addMinutes(30));

                return;
            }

            if (! is_string($generatedData)) {
                Cache::put($cacheKey, [
                    'status' => 'failed',
                    'message' => 'Неожиданный формат данных от API',
                ], now()->addMinutes(30));

                return;
            }

            $parsed = $this->parseGeneratedProductData($generatedData);
            if (! $parsed) {
                Cache::put($cacheKey, [
                    'status' => 'failed',
                    'message' => 'Не удалось распарсить данные продукта',
                ], now()->addMinutes(30));

                return;
            }

            Cache::put($cacheKey, [
                'status' => 'ready',
                'product_data' => [
                    'name' => $this->productName,
                    'calories' => $parsed['calories'] ?? 0,
                    'proteins' => $parsed['proteins'] ?? 0,
                    'carbohydrates' => $parsed['carbohydrates'] ?? 0,
                    'fats' => $parsed['fats'] ?? 0,
                    'fibers' => $parsed['fibers'] ?? 0,
                ],
            ], now()->addMinutes(30));
        } catch (\Throwable $e) {
            Log::error('GenerateProductDataJob failed: '.$e->getMessage(), [
                'rid' => $this->rid,
                'trace' => $e->getTraceAsString(),
            ]);

            Cache::put($cacheKey, [
                'status' => 'failed',
                'message' => 'Произошла ошибка при генерации данных продукта: '.$e->getMessage(),
            ], now()->addMinutes(30));

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Cache::put('voice:job:'.$this->rid, [
            'status' => 'failed',
            'message' => 'Произошла ошибка при генерации данных продукта: '.$e->getMessage(),
        ], now()->addMinutes(30));
    }

    private function parseGeneratedProductData(string $generatedData): ?array
    {
        try {
            if (str_starts_with($generatedData, '{') && str_ends_with($generatedData, '}')) {
                $data = json_decode($generatedData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
            }

            $data = [];

            // RU
            if (preg_match('/Калории[\s\-]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['calories'] = (float) $m[1];
            }
            if (preg_match('/Белки[\s\-]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['proteins'] = (float) $m[1];
            }
            if (preg_match('/Углеводы[\s\-]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['carbohydrates'] = (float) $m[1];
            }
            if (preg_match('/Жиры[\s\-]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['fats'] = (float) $m[1];
            }
            if (preg_match('/Клетчатка[\s\-]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['fibers'] = (float) $m[1];
            }

            // UA
            if (! isset($data['calories']) && preg_match('/Калорії[\s\-]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['calories'] = (float) $m[1];
            }
            if (! isset($data['proteins']) && preg_match('/Білки[\s\-]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['proteins'] = (float) $m[1];
            }
            if (! isset($data['carbohydrates']) && preg_match('/Вуглеводи[\s\-]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['carbohydrates'] = (float) $m[1];
            }
            if (! isset($data['fats']) && preg_match('/Жири[\s\-]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['fats'] = (float) $m[1];
            }
            if (! isset($data['fibers']) && preg_match('/Клітковина[\s\-]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['fibers'] = (float) $m[1];
            }

            // EN
            if (! isset($data['calories']) && preg_match('/calories[\s\:]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['calories'] = (float) $m[1];
            }
            if (! isset($data['proteins']) && preg_match('/proteins[\s\:]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['proteins'] = (float) $m[1];
            }
            if (! isset($data['carbohydrates']) && preg_match('/carbohydrates[\s\:]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['carbohydrates'] = (float) $m[1];
            }
            if (! isset($data['fats']) && preg_match('/fats[\s\:]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['fats'] = (float) $m[1];
            }
            if (! isset($data['fibers']) && preg_match('/fibers[\s\:]+(\d+\.?\d*)/i', $generatedData, $m)) {
                $data['fibers'] = (float) $m[1];
            }

            if (empty($data)) {
                $parts = preg_split('/[;,]/', $generatedData);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (preg_match('/([а-яА-Яa-zA-Zіїєґ]+)[\s\-:]+(\d+\.?\d*)/u', $part, $m)) {
                        $key = strtolower(trim($m[1]));
                        $value = (float) $m[2];
                        switch ($key) {
                            case 'калории':
                            case 'калорії':
                            case 'calories':
                            case 'ккал':
                                $data['calories'] = $value;
                                break;
                            case 'белки':
                            case 'білки':
                            case 'proteins':
                            case 'протеин':
                                $data['proteins'] = $value;
                                break;
                            case 'углеводы':
                            case 'вуглеводи':
                            case 'carbohydrates':
                            case 'carbs':
                                $data['carbohydrates'] = $value;
                                break;
                            case 'жиры':
                            case 'жири':
                            case 'fats':
                                $data['fats'] = $value;
                                break;
                            case 'клетчатка':
                            case 'клітковина':
                            case 'fibers':
                                $data['fibers'] = $value;
                                break;
                        }
                    }
                }
            }

            if (! isset($data['fibers'])) {
                $data['fibers'] = 0;
            }

            if (isset($data['calories']) || isset($data['proteins']) || isset($data['fats'])) {
                return $data;
            }
        } catch (\Throwable $e) {
            Log::error('Ошибка парсинга данных продукта: '.$e->getMessage(), [
                'rid' => $this->rid,
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return null;
    }
}
