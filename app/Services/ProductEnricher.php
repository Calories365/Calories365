<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gives products of the database their sugars, glycemic index and NOVA group
 * (ProductQuality), asked of the product model for many products at once.
 *
 * New products get them when they are made (ProductCatalog, labels); the
 * thousands of older ones get them when somebody eats them: the products of a
 * calculated meal draft at once, the others by the scheduled products:enrich.
 * A product the model answered about is not asked again.
 */
class ProductEnricher
{
    /**
     * Products of one request to the model.
     */
    public const BATCH = 25;

    /**
     * Ask about the products among these that were never asked about.
     *
     * @param  array<int, int>  $productIds
     * @return int how many products got an answer
     */
    public function enrich(array $productIds, string $locale = 'ru'): int
    {
        $products = Product::query()
            ->whereIn('id', array_unique($productIds))
            ->whereNull('quality_source')
            ->with('translations')
            ->get();

        $done = 0;
        foreach ($products->chunk(self::BATCH) as $batch) {
            try {
                $done += $this->enrichBatch($batch->values(), $locale);
            } catch (\Throwable $e) {
                Log::warning('Product quality was not given', ['error' => $e->getMessage()]);
            }
        }

        return $done;
    }

    /**
     * @param  Collection<int, Product>  $products
     *
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \RuntimeException
     */
    private function enrichBatch(Collection $products, string $locale): int
    {
        $lines = [];
        foreach ($products as $index => $product) {
            $name = $product->translations->firstWhere('locale', $locale)->name
                ?? $product->translations->first()->name
                ?? '';
            $lines[] = sprintf(
                '%d. %s — per 100 g: %s kcal, proteins %s, fats %s, carbohydrates %s',
                $index,
                $name,
                $product->calories,
                $product->proteins,
                $product->fats,
                $product->carbohydrates
            );
        }

        $prompt = "For each food of a calorie diary, with its nutrition facts, give:\n"
            .ProductQuality::instructions()."\n\n"
            ."Foods:\n".implode("\n", $lines);

        $keys = config('services.openai.keys');

        $result = Http::timeout(60)
            ->withToken((string) ($keys[$locale] ?? $keys['ru'] ?? ''))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.models.product'),
                'reasoning_effort' => config('services.openai.reasoning_effort'),
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'product_quality',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['items'],
                            'properties' => [
                                'items' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'additionalProperties' => false,
                                        'required' => ['index', ...ProductQuality::FIELDS],
                                        'properties' => ['index' => ['type' => 'integer']] + ProductQuality::schema(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ])
            ->throw()
            ->json();

        $decoded = json_decode((string) ($result['choices'][0]['message']['content'] ?? ''), true);

        if (! is_array($decoded) || ! is_array($decoded['items'] ?? null)) {
            throw new \RuntimeException('The product model gave no JSON');
        }

        $done = 0;
        foreach ($decoded['items'] as $item) {
            $product = is_array($item) ? $products->get((int) ($item['index'] ?? -1)) : null;

            if ($product === null || $product->quality_source !== null) {
                continue;
            }

            $product->update(ProductQuality::valid($item, (float) $product->carbohydrates) + ['quality_source' => Product::SOURCE_AI]);
            $done++;
        }

        return $done;
    }
}
