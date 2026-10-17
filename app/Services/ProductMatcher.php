<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Meilisearch\Client;
use Meilisearch\Contracts\SearchQuery;

/**
 * Finds the database product for each food a user named, without a model.
 *
 * A cooking method changes the nutrition facts several times over, so a near
 * match is worse than none: "Гречка" is boiled buckwheat, not the dry groats
 * the database lists first, and "Гречка варёная" is not "гречка вареная с луком
 * и морковью". FoodName reads the said name and every product name alike: the
 * stems of the food, its cooking method, what was added. The index keeps the
 * stems and the method of each product, so one multi-search request finds, for
 * all items of a meal at once, the products with the same food words and a
 * cooking method that fits.
 *
 * Of those, a product that is another food or adds something the user did not
 * name is left out, and so is one whose nutrition facts cannot be true. The rest
 * are ranked the way calorie diaries rank their search: the same cooking method
 * first, then what the user has eaten before, then the fewest words the user did
 * not say, then names in the user's language, verified products, products people
 * entered over estimated ones, and the most eaten.
 *
 * An item with no fitting product gets null; ProductCatalog then adds it.
 */
class ProductMatcher
{
    private const CANDIDATES_PER_ITEM = 50;

    /**
     * FoodName::penaltyFor beyond which a product is taken for another food: a
     * word the user did not say, or the fat content. A product not found is added
     * by ProductCatalog, which costs less than the wrong one.
     */
    private const MAX_PENALTY = 2;

    /**
     * A grain boiled in water holds under 200 kcal in 100 g; a "boiled" product
     * above that carries the facts of the dry grain.
     */
    private const MAX_BOILED_GRAIN_CALORIES = 200;

    private const MIN_BOILED_GRAIN_CALORIES = 60;

    public function __construct(private Client $client) {}

    /**
     * @param  array<int, string>  $names  the foods as the user named them
     * @return array<int, array<string, mixed>|null> per name: the chosen product translation hit, or null
     */
    public function match(array $names, ?int $userId, string $locale): array
    {
        try {
            return $this->find($names, $userId, $locale);
        } catch (\Throwable $e) {
            Log::warning('Product search failed', ['error' => $e->getMessage()]);

            return array_fill(0, count($names), null);
        }
    }

    /**
     * Like match(), but a failed search is thrown: a caller that adds the missing
     * products must not take every item for missing.
     *
     * @param  array<int, string>  $names
     * @return array<int, array<string, mixed>|null>
     *
     * @throws \Throwable
     */
    public function find(array $names, ?int $userId, string $locale): array
    {
        $said = array_map(static fn (string $name) => FoodName::parse($name), array_values($names));

        if ($said === []) {
            return [];
        }

        $hits = $this->search($said, $userId, $locale);
        $productIds = [];
        foreach ($hits as $itemHits) {
            foreach ($itemHits as $hit) {
                $productIds[(int) $hit['product_id']] = (int) $hit['product_id'];
            }
        }

        $facts = Product::matchingFacts(array_values($productIds), $userId);
        $foods = $this->foodWords();

        return array_map(
            fn (int $index) => $this->best($said[$index], $hits[$index] ?? [], $facts, $foods, $userId, $locale),
            array_keys($said)
        );
    }

    /**
     * Index hits per item: products with the same food words and a fitting
     * cooking method, visible to the user. The seam tests replace.
     *
     * @param  array<int, FoodName>  $said
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function search(array $said, ?int $userId, string $locale): array
    {
        $queries = [];
        $items = [];

        foreach ($said as $index => $name) {
            if ($name->stems() === []) {
                continue;
            }

            $cooking = implode(', ', array_map(
                static fn (string $method) => '"'.$method.'"',
                array_keys($name->acceptedCooking())
            ));

            $queries[] = (new SearchQuery)
                ->setIndexUid((new ProductTranslation)->searchableAs())
                ->setQuery($name->queryText())
                ->setAttributesToSearchOn(['stems'])
                ->setMatchingStrategy('all')
                ->setFilter([
                    'locale = "'.addslashes($locale).'"',
                    $this->visibleTo($userId),
                    "cooking IN [{$cooking}]",
                ])
                ->setLimit(self::CANDIDATES_PER_ITEM);
            $items[] = $index;
        }

        if ($queries === []) {
            return [];
        }

        $results = $this->client->multiSearch($queries)['results'] ?? [];

        $hits = [];
        foreach ($items as $position => $index) {
            $hits[$index] = $results[$position]['hits'] ?? [];
        }

        return $hits;
    }

    /**
     * The user's own products, the shared ones and the verified products of others.
     */
    private function visibleTo(?int $userId): string
    {
        return $userId === null
            ? '(user_id IS NULL OR verified = 1)'
            : "((user_id = {$userId} AND active = 1) OR user_id IS NULL OR verified = 1)";
    }

    /**
     * @param  array<int, array<string, mixed>>  $hits
     * @param  array<int, array{calories: float, proteins: float, fats: float, carbohydrates: float, uses: int, mine: int, source: string|null}>  $facts
     * @param  array<string, true>  $foods
     * @return array<string, mixed>|null
     */
    private function best(FoodName $said, array $hits, array $facts, array $foods, ?int $userId, string $locale): ?array
    {
        $accepted = $said->acceptedCooking();
        $fitting = [];

        foreach ($hits as $position => $hit) {
            $name = (string) $hit['name'];
            $product = FoodName::parse($name);
            $fact = $facts[(int) $hit['product_id']] ?? null;
            $penalty = $said->penaltyFor($product, $locale !== 'en', $foods);
            $foreign = $this->inAnotherLanguage($name, $locale);

            // The index may hold a name read by older rules; the method is checked again.
            if (! isset($accepted[$product->cooking]) || $fact === null || ! $this->plausible($product, $fact)
                || $penalty === null || $penalty > self::MAX_PENALTY || ($foreign && $locale === 'en')) {
                continue;
            }

            $fitting[] = [
                'hit' => $hit,
                'calories' => $fact['calories'],
                'rank' => [
                    $accepted[$product->cooking],
                    -$fact['mine'],
                    $penalty,
                    (int) $foreign,
                    isset($hit['user_id']) && $userId !== null && (int) $hit['user_id'] === $userId ? 0 : 1,
                    ! empty($hit['verified']) ? 0 : 1,
                    $fact['source'] === Product::SOURCE_AI ? 1 : 0,
                ],
                'tail' => [-$fact['uses'], mb_strlen($name), $position],
            ];
        }

        if ($fitting === []) {
            return null;
        }

        $median = $this->medianCalories($fitting);
        foreach ($fitting as &$candidate) {
            $candidate['rank'] = [...$candidate['rank'], $this->deviation($candidate['calories'], $median), ...$candidate['tail']];
        }
        unset($candidate);

        usort($fitting, static fn (array $a, array $b) => $a['rank'] <=> $b['rank']);

        return $fitting[0]['hit'];
    }

    /**
     * The median calories of the candidates as close to the named food as the
     * closest one: the same cooking tier and as few words the user did not say.
     *
     * Several products of the same food are entered by different people, and one
     * of them now and then holds wrong facts ("Курица на пару", 56 kcal). The
     * agreeing majority shows which.
     *
     * @param  array<int, array{calories: float, rank: array<int, int>}>  $fitting
     */
    private function medianCalories(array $fitting): float
    {
        $closest = min(array_map(static fn (array $candidate) => [$candidate['rank'][0], $candidate['rank'][2]], $fitting));

        $calories = [];
        foreach ($fitting as $candidate) {
            if ([$candidate['rank'][0], $candidate['rank'][2]] === $closest) {
                $calories[] = $candidate['calories'];
            }
        }

        sort($calories);
        $middle = intdiv(count($calories), 2);

        return count($calories) % 2 === 1 ? $calories[$middle] : ($calories[$middle - 1] + $calories[$middle]) / 2;
    }

    /**
     * How far the calories are from the median: 0 within a fifth of it, 1 within
     * a half, 2 beyond.
     */
    private function deviation(float $calories, float $median): int
    {
        $share = abs($calories - $median) / max($median, 1.0);

        return $share <= 0.2 ? 0 : ($share <= 0.5 ? 1 : 2);
    }

    /**
     * Whether the nutrition facts can be those of the named food.
     *
     * Macros worth more energy than the calories are a typo or a test entry ("мед",
     * 11 kcal and 11 g of everything); fewer are common, the macros often go
     * unfilled. A boiled grain with the calories of a dry one, or of a soup, has
     * the wrong facts.
     *
     * @param  array{calories: float, proteins: float, fats: float, carbohydrates: float}  $fact
     */
    public function plausible(FoodName $product, array $fact): bool
    {
        $fromMacros = 4 * $fact['proteins'] + 4 * $fact['carbohydrates'] + 9 * $fact['fats'];

        if ($fromMacros > $fact['calories'] * 1.35 + 40) {
            return false;
        }

        if (! $product->isGrain() || ! in_array($product->cooking, [FoodName::BOILED, FoodName::STEAMED, FoodName::COOKED], true)) {
            return true;
        }

        return $fact['calories'] >= self::MIN_BOILED_GRAIN_CALORIES && $fact['calories'] <= self::MAX_BOILED_GRAIN_CALORIES;
    }

    /**
     * Stems of the foods the database names in one word, see FoodName::foodWords.
     *
     * @return array<string, true>
     */
    private function foodWords(): array
    {
        return Cache::remember('product-matcher:food-words', now()->addDay(),
            static fn () => FoodName::foodWords(ProductTranslation::oneWordNames()));
    }

    /**
     * A name written in another language than the locale: shared products got
     * every translation with the name their author typed.
     */
    private function inAnotherLanguage(string $name, string $locale): bool
    {
        return (bool) match ($locale) {
            'ru' => preg_match('/[іїєґ]/u', mb_strtolower($name)),
            'ua' => preg_match('/[ыэъё]/u', mb_strtolower($name)),
            default => preg_match('/\p{Cyrillic}/u', $name),
        };
    }
}
