<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\AudioConversionService;
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
use Illuminate\Support\Facades\Storage;

class ProcessVoiceRecordJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 180;

    public $backoff = [5, 10, 20, 30];

    public int $uniqueFor = 600; // 10 minutes

    public function middleware(): array
    {
        // Re-use the existing OpenAI rate limiter
        return [new RateLimited('openai-feedback')];
    }

    public function uniqueId(): string
    {
        return 'voice|'.$this->rid;
    }

    public function __construct(
        public int $userId,
        public string $rid,
        public string $localPath,
        public string $locale
    ) {
        $this->onQueue('AI');
    }

    public function handle(AudioConversionService $audioConversionService, SpeechToTextService $speechToTextService): void
    {
        // Ensure the job uses the same locale as the request
        App::setLocale($this->locale);

        $cacheKey = 'voice:job:'.$this->rid;

        try {
            $fullPath = Storage::disk('public')->path($this->localPath);

            [$mp3Local, $mp3Full] = $audioConversionService->convertToMp3($this->localPath, $fullPath);
            $fileForStt = $mp3Full ?: $fullPath;

            $text = $speechToTextService->convertSpeechToText($fileForStt);

            // Clean up files regardless of STT result
            try {
                Storage::disk('public')->delete($this->localPath);
            } catch (\Throwable $cleanupE) {
                Log::warning('Failed to delete source voice file', ['rid' => $this->rid, 'path' => $this->localPath]);
            }

            if ($mp3Local) {
                try {
                    Storage::disk('public')->delete($mp3Local);
                } catch (\Throwable $cleanupE) {
                    Log::warning('Failed to delete converted mp3', ['rid' => $this->rid, 'path' => $mp3Local]);
                }
            }

            if (is_array($text) && isset($text['error'])) {
                Cache::put($cacheKey, [
                    'status' => 'failed',
                    'message' => 'Ошибка при расшифровке: '.$text['error'],
                ], now()->addMinutes(30));

                return;
            }

            $products = $this->searchProductsFromTranscription($text, $this->userId, $this->locale, $speechToTextService);

            Cache::put($cacheKey, [
                'status' => 'ready',
                'transcription' => $text,
                'products' => $products,
            ], now()->addMinutes(30));
        } catch (\Throwable $e) {
            Log::error('ProcessVoiceRecordJob failed: '.$e->getMessage(), [
                'rid' => $this->rid,
                'trace' => $e->getTraceAsString(),
            ]);

            Cache::put($cacheKey, [
                'status' => 'failed',
                'message' => 'Произошла ошибка при обработке записи: '.$e->getMessage(),
            ], now()->addMinutes(30));

            throw $e; // allow retry/backoff
        }
    }

    public function failed(\Throwable $e): void
    {
        Cache::put('voice:job:'.$this->rid, [
            'status' => 'failed',
            'message' => 'Произошла ошибка при обработке записи: '.$e->getMessage(),
        ], now()->addMinutes(30));
    }

    private function searchProductsFromTranscription(string $text, int $userId, string $locale, SpeechToTextService $stt): array
    {
        $suffix = 'грамм';
        switch ($locale) {
            case 'en':
                $suffix = 'grams';
                break;
            case 'ua':
                $suffix = 'грам';
                break;
            case 'ru':
            default:
                $suffix = 'грамм';
                break;
        }

        if (str_contains($text, ';')) {
            $items = explode(';', $text);
        } else {
            $items = explode($suffix, $text);
        }

        $items = array_filter($items, fn ($i) => trim($i) !== '');

        $result = [];
        foreach ($items as $product) {
            $product = trim($product);
            if ($product === '') {
                continue;
            }

            $parts = explode(' - ', $product);
            if (count($parts) <= 1) {
                Log::warning("Некорректный формат продукта: {$product}", ['rid' => $this->rid]);

                continue;
            }

            $productName = trim($parts[0]);
            $quantityStr = trim($parts[1]);
            if (preg_match('/(\d+(\.\d+)?)/', $quantityStr, $matches)) {
                $quantity = (float) $matches[1];
                if ($quantity <= 0) {
                    Log::warning("Некорректное количество продукта: {$productName}", ['rid' => $this->rid]);

                    continue;
                }

                $productData = $this->findProduct($productName, $quantity, $userId, $locale);
                if ($productData) {
                    $result[] = $productData;
                } else {
                    $generated = $this->generateProductsData($productName, $quantity, $stt);
                    if ($generated) {
                        $result[] = $generated;
                    }
                }
            } else {
                Log::warning("Ошибка при извлечении количества: {$quantityStr}", ['rid' => $this->rid]);
            }
        }

        return $result;
    }

    private function findProduct(string $productName, float $quantity, int $userId, string $locale): ?array
    {
        $productTranslation = Product::getRawProduct($productName, $userId, $locale);

        if (is_array($productTranslation) && isset($productTranslation['_rankingScore']) && $productTranslation['_rankingScore'] < 0.6) {
            return null;
        }

        if (is_array($productTranslation)) {
            $product = Product::find($productTranslation['product_id']);
            if ($product && $product->calories !== null) {

                return [
                    'product_translation' => [
                        'id' => $productTranslation['id'],
                        'product_id' => $productTranslation['product_id'],
                        'locale' => $productTranslation['locale'],
                        'name' => $productTranslation['name'],
                        'user_id' => $productTranslation['user_id'] ?? null,
                        'said_name' => $productName,
                        'original_name' => $productName,
                    ],
                    'product' => [
                        'id' => $product->id,
                        'user_id' => $product->user_id,
                        'calories' => $product->calories,
                        'proteins' => $product->proteins,
                        'carbohydrates' => $product->carbohydrates,
                        'fats' => $product->fats,
                        'fibers' => $product->fibers ?? 0,
                        'quantity' => $quantity,
                    ],
                    'quantity' => $quantity,
                ];
            }
        }

        return null;
    }

    private function generateProductsData(string $productName, float $quantity, SpeechToTextService $stt): ?array
    {
        try {
            $generatedData = $stt->generateNewProductData($productName);

            if (is_array($generatedData) && isset($generatedData['error'])) {
                Log::error('Ошибка генерации данных продукта: '.$generatedData['error'], ['rid' => $this->rid]);

                return null;
            }

            if (! is_string($generatedData)) {
                Log::error('Неожиданный формат данных от OpenAI', ['rid' => $this->rid, 'data' => $generatedData]);

                return null;
            }

            $nutritionData = $this->parseGeneratedProductData($generatedData);
            if ($nutritionData) {
                return [
                    'product_translation' => [
                        'id' => null,
                        'product_id' => null,
                        'locale' => $this->locale,
                        'name' => $productName,
                        'user_id' => $this->userId,
                        'said_name' => $productName,
                        'original_name' => $productName,
                        'is_generated' => true,
                    ],
                    'product' => [
                        'id' => null,
                        'user_id' => $this->userId,
                        'calories' => $nutritionData['calories'] ?? 0,
                        'proteins' => $nutritionData['proteins'] ?? 0,
                        'carbohydrates' => $nutritionData['carbohydrates'] ?? 0,
                        'fats' => $nutritionData['fats'] ?? 0,
                        'fibers' => $nutritionData['fibers'] ?? 0,
                        'quantity' => $quantity,
                    ],
                    'quantity' => $quantity,
                    'is_generated' => true,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при генерации данных продукта: '.$e->getMessage(), [
                'rid' => $this->rid,
                'product' => $productName,
                'quantity' => $quantity,
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return null;
    }

    private function parseGeneratedProductData(string $generatedData): ?array
    {
        try {
            // JSON case
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
        } catch (\Exception $e) {
            Log::error('Ошибка при парсинге данных продукта: '.$e->getMessage(), [
                'rid' => $this->rid,
                'input' => $generatedData,
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return null;
    }
}
