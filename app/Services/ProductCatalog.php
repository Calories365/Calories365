<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\ProductTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Finds the products of a meal and adds the ones the database lacks.
 *
 * ProductMatcher answers from the database. A food it finds nothing for gets
 * its nutrition facts estimated by the model, in one request for all such items
 * of the meal, and becomes a shared product (source 'ai') named in every
 * language, tied to the said name by a ProductAlias. The next user who names the
 * same food gets it from the database: the model is asked only about foods the
 * database has never seen, so its share falls as the database fills.
 */
class ProductCatalog
{
    /**
     * Items of one meal the model is asked about; the rest stay not found.
     */
    private const MAX_GENERATED = 10;

    private const NAME_FIELDS = ['ru' => 'name_ru', 'ua' => 'name_ua', 'en' => 'name_en'];

    public function __construct(private ProductMatcher $matcher) {}

    /**
     * @param  array<int, string>  $names  the foods as the user named them
     * @return array<int, array<string, mixed>|null> per name: the product translation hit, or null
     */
    public function find(array $names, ?int $userId, string $locale): array
    {
        $names = array_values($names);

        try {
            $hits = $this->matcher->find($names, $userId, $locale);
        } catch (\Throwable $e) {
            // Every item would look missing; generating them all would fill the database with duplicates.
            Log::warning('Product search failed', ['error' => $e->getMessage()]);

            return array_fill(0, count($names), null);
        }

        $missing = $this->missing($hits, $names);
        foreach (ProductAlias::translationsFor(array_map(static fn (int $index) => $names[$index], $missing), $locale) as $position => $translation) {
            $hits[$missing[$position]] = self::hit($translation);
        }

        $missing = $this->missing($hits, $names);
        if ($missing === []) {
            return $hits;
        }

        // One generated product per food, however many times the meal names it.
        $groups = [];
        foreach ($missing as $index) {
            $groups[ProductAlias::keyFor($names[$index]) ?? $index][] = $index;
        }
        $groups = array_values(array_slice($groups, 0, self::MAX_GENERATED));

        try {
            $generated = $this->generate(array_map(static fn (array $group) => $names[$group[0]], $groups), $locale);
        } catch (\Throwable $e) {
            Log::warning('Product generation failed', ['error' => $e->getMessage()]);

            return $hits;
        }

        $known = $this->knownUnderGeneratedNames($generated, $userId, $locale);

        foreach ($groups as $position => $group) {
            $facts = $generated[$position] ?? null;
            if ($facts === null) {
                continue;
            }

            $hit = $known[$position] ?? $this->store($facts, $locale);

            if ($hit['user_id'] === null) {
                ProductAlias::remember($names[$group[0]], $locale, (int) $hit['product_id']);
            }

            foreach ($group as $index) {
                $hits[$index] = $hit;
            }
        }

        return $hits;
    }

    /**
     * Products of a brand the user named or a package shows. The generic food
     * has other facts, so a branded food never takes it: it takes an earlier
     * product of the same brand and the same food, or the model estimates the
     * facts of the branded food and it becomes one shared product under its
     * branded name, which the next mention of the brand finds.
     *
     * @param  array<int, array{name: string, brand: string}>  $foods
     * @return array<int, array<string, mixed>|null> per food: the product translation hit, or null
     */
    public function findBranded(array $foods, ?int $userId, string $locale): array
    {
        $hits = [];
        $missing = [];

        foreach ($foods as $index => $food) {
            $known = $this->knownBranded($food['name'], $food['brand'], $userId, $locale);
            $hits[$index] = $known ? self::hit($known) : null;

            if ($known === null) {
                $missing[$index] = $food['name'];
            }
        }

        if ($missing === []) {
            return $hits;
        }

        $indexes = array_keys($missing);

        try {
            $generated = $this->generate(array_values($missing), $locale);
        } catch (\Throwable $e) {
            Log::warning('Branded product generation failed', ['error' => $e->getMessage()]);

            return $hits;
        }

        foreach ($indexes as $position => $index) {
            $facts = $generated[$position] ?? null;
            if ($facts === null) {
                continue;
            }

            $hits[$index] = $this->store($facts, $locale);
            ProductAlias::remember($missing[$index], $locale, (int) $hits[$index]['product_id']);
        }

        return $hits;
    }

    /**
     * The product of the brand and of the same food: "Творог Простоквашино 5%"
     * finds "Творог 5% Простоквашино Danone", or "Творог Простоквашино" without
     * a fat content, never "Творог 2% Простоквашино", "Молоко Простоквашино" or
     * plain "Творог"; "Сырок с изюмом" never finds "Сырок с абрикосом". Shared
     * products and the user's own.
     */
    private function knownBranded(string $name, string $brand, ?int $userId, string $locale): ?ProductTranslation
    {
        $key = self::brandKey($brand);
        $isBrand = static fn (string $word) => $key !== '' && str_starts_with($word, $key);

        // Additions come in parts of several words, and the brand may stick to one.
        $added = static fn (FoodName $parsed) => array_values(array_filter(
            array_merge([], ...$parsed->additions),
            static fn (string $word) => ! $isBrand($word)
        ));

        $said = FoodName::parse($name);
        $food = array_values(array_filter($said->food, static fn (string $word) => ! $isBrand($word)));
        $additions = $added($said);
        $numbers = $said->numbers;
        sort($numbers);

        if ($food === [] || $key === '') {
            return null;
        }

        $alias = ProductAlias::translationsFor([$name], $locale)[0] ?? null;
        if ($alias !== null) {
            return $alias;
        }

        return ProductTranslation::query()
            ->where('locale', $locale)
            ->where('name', 'like', '%'.addcslashes($key, '%_\\').'%')
            ->where(static fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $userId))
            ->limit(50)
            ->get()
            ->map(static function (ProductTranslation $translation) use ($food, $numbers, $additions, $added, $isBrand) {
                $known = FoodName::parse((string) $translation->name);
                $words = array_values(array_filter($known->food, static fn (string $word) => ! $isBrand($word)));
                $knownAdditions = $added($known);
                $knownNumbers = $known->numbers;
                sort($knownNumbers);

                // The kind of food, the first word, must match; a fat content or a
                // weight both names give must be the same, and so must what both
                // say was added. The rest only ranks.
                if (! in_array($food[0], $words, true)
                    || ($numbers !== [] && $knownNumbers !== [] && $numbers !== $knownNumbers)
                    || ($additions !== [] && $knownAdditions !== [] && array_intersect($additions, $knownAdditions) === [])) {
                    return null;
                }

                $rank = ($numbers !== [] && $numbers === $knownNumbers ? 100 : 0)
                    + 10 * count(array_intersect($additions, $knownAdditions))
                    - 10 * count(array_diff($additions, $knownAdditions))
                    - 5 * count(array_diff($knownAdditions, $additions))
                    + count(array_intersect($food, $words)) - count(array_diff($words, $food));

                return ['translation' => $translation, 'rank' => $rank];
            })
            ->filter()
            ->sortByDesc('rank')
            ->first()['translation'] ?? null;
    }

    /**
     * The start of a brand that its other forms share: "Яготинская",
     * "Яготинська" and "Яготинский" all begin with "яготинс".
     */
    private static function brandKey(string $brand): string
    {
        $brand = mb_strtolower(trim($brand));
        $length = mb_strlen($brand);

        return mb_substr($brand, 0, min($length, max(4, $length - 3)));
    }

    /**
     * Positions of the items without a product.
     *
     * @param  array<int, array<string, mixed>|null>  $hits
     * @param  array<int, string>  $names
     * @return array<int, int>
     */
    private function missing(array $hits, array $names): array
    {
        return array_values(array_filter(array_keys($names), static fn (int $index) => ($hits[$index] ?? null) === null));
    }

    /**
     * Products the database already has under the names the model gave: "Нога
     * куриная" said, "Куриная ножка варёная" known.
     *
     * @param  array<int, array<string, mixed>|null>  $generated
     * @return array<int, array<string, mixed>>
     */
    private function knownUnderGeneratedNames(array $generated, ?int $userId, string $locale): array
    {
        $named = array_filter($generated);
        if ($named === []) {
            return [];
        }

        try {
            $hits = $this->matcher->find(array_map(static fn (array $facts) => $facts['names'][$locale], $named), $userId, $locale);
        } catch (\Throwable) {
            return [];
        }

        return array_filter(array_combine(array_keys($named), $hits));
    }

    /**
     * Nutrition facts and names of the foods, from the model; null for a name
     * that is not a food or got facts that cannot be true.
     *
     * @param  array<int, string>  $names
     * @return array<int, array{names: array<string, string>, calories: float, proteins: float, fats: float, carbohydrates: float, fibers: float}|null>
     *
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \RuntimeException
     */
    private function generate(array $names, string $locale): array
    {
        $lines = [];
        foreach ($names as $index => $name) {
            $lines[] = $index.'. '.$name;
        }

        $prompt = "A calorie diary has no product for these foods a user named (in {$this->languageName($locale)}):\n"
            .implode("\n", $lines)."\n\n"
            ."For each item:\n"
            ."- is_food: false for anything that is not a food or a drink; the other fields are then ignored.\n"
            .'- name_ru, name_ua, name_en: the food in Russian, Ukrainian and English, the way a nutrition database '
            .'names it: the food first, then what describes it, no parentheses. Keep the cooking method, the sort, the fat content, '
            .'the brand and what was added as the user said them, and add nothing else. When no cooking method was '
            .'said and the food is eaten only cooked (grains, pasta, legumes, potatoes, meat, fish), name the usual '
            ."method: \"Гречка варёная\", \"Стейк говяжий жареный\".\n"
            .'- calories (kcal), proteins, fats, carbohydrates, fibers (g): per 100 g of the food as eaten, in the '
            ."state the names say.\n"
            .ProductQuality::instructions();

        $number = ['type' => 'number'];
        $keys = config('services.openai.keys');

        $result = Http::timeout(30)
            ->withToken((string) ($keys[$locale] ?? $keys['ru'] ?? ''))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.models.product'),
                'reasoning_effort' => config('services.openai.reasoning_effort'),
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'missing_products',
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
                                        'required' => ['index', 'is_food', 'name_ru', 'name_ua', 'name_en', 'calories', 'proteins', 'fats', 'carbohydrates', 'fibers', ...ProductQuality::FIELDS],
                                        'properties' => [
                                            'index' => ['type' => 'integer'],
                                            'is_food' => ['type' => 'boolean'],
                                            'name_ru' => ['type' => 'string'],
                                            'name_ua' => ['type' => 'string'],
                                            'name_en' => ['type' => 'string'],
                                            'calories' => $number,
                                            'proteins' => $number,
                                            'fats' => $number,
                                            'carbohydrates' => $number,
                                            'fibers' => $number,
                                        ] + ProductQuality::schema(),
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

        if (! is_array($decoded)) {
            throw new \RuntimeException('The product model gave no JSON');
        }

        $generated = array_fill_keys(array_keys($names), null);
        foreach ($decoded['items'] ?? [] as $item) {
            $index = (int) ($item['index'] ?? -1);

            if (array_key_exists($index, $generated)) {
                $generated[$index] = $this->validFacts($item);
            }
        }

        return $generated;
    }

    /**
     * The item as stored facts, or null when it is not a food, has a name
     * missing or facts that cannot be true: the matcher would never pick such a
     * product, and a model's slip must not become a shared product.
     *
     * @param  array<string, mixed>  $item
     * @return array{names: array<string, string>, calories: float, proteins: float, fats: float, carbohydrates: float, fibers: float}|null
     */
    private function validFacts(array $item): ?array
    {
        if (($item['is_food'] ?? false) !== true) {
            return null;
        }

        $names = [];
        foreach (self::NAME_FIELDS as $locale => $field) {
            $name = trim((string) ($item[$field] ?? ''));
            if ($name === '' || mb_strlen($name) > 150) {
                return null;
            }
            $names[$locale] = $name;
        }

        $values = [];
        foreach (['calories', 'proteins', 'fats', 'carbohydrates', 'fibers'] as $field) {
            $value = $item[$field] ?? null;
            if (! is_numeric($value) || $value < 0 || $value > ($field === 'calories' ? 950 : 100)) {
                return null;
            }
            $values[$field] = round((float) $value, 2);
        }

        if ($values['proteins'] + $values['fats'] + $values['carbohydrates'] > 105
            || ! $this->matcher->plausible(FoodName::parse($names['ru']), $values)) {
            return null;
        }

        return ['names' => $names] + $values + ProductQuality::valid($item, $values['carbohydrates']);
    }

    /**
     * The product of a label read from a photo (MealInputReader).
     *
     * A label tells what is in the package, so a named product joins the shared
     * database, and the same label photographed again finds the product it made.
     * A label without the product's name makes the user's own product: a shared
     * "Продукт с этикетки" would match nothing anyone says.
     *
     * @param  array{names: array<string, string>, named: bool, calories: float, proteins: float, fats: float, carbohydrates: float, fibers: float, sugars?: float|null, glycemic_index?: int|null, nova?: int|null}  $label
     * @return array<string, mixed> the hit of the translation in the locale
     */
    public function addFromLabel(array $label, string $locale, int $userId): array
    {
        if (! $label['named']) {
            return $this->store($label, $locale, Product::SOURCE_LABEL, $userId);
        }

        $known = $this->knownLabel($label, $locale);

        // A product an earlier photo made before labels gave sugars and the GI.
        $knownProduct = $known?->product;
        if ($knownProduct instanceof Product && $knownProduct->quality_source === null) {
            $knownProduct->update(array_filter(array_intersect_key($label, array_flip(ProductQuality::FIELDS)), static fn ($value) => $value !== null) + ['quality_source' => Product::SOURCE_LABEL]);
        }

        $hit = $known ? self::hit($known) : $this->store($label, $locale, Product::SOURCE_LABEL);
        ProductAlias::remember($hit['name'], $locale, (int) $hit['product_id']);

        return $hit;
    }

    /**
     * The shared product an earlier photo of the same label made: the same
     * nutrition facts and a name of the same food. The model spells a brand
     * differently from photo to photo, "Olympus" and "OLYMPOS", so the name is
     * compared by its food words only.
     *
     * @param  array{names: array<string, string>, calories: float, proteins: float, fats: float, carbohydrates: float}  $label
     */
    private function knownLabel(array $label, string $locale): ?ProductTranslation
    {
        $food = FoodName::parse($label['names'][$locale] ?? $label['names']['ru'])->food;

        $translations = ProductTranslation::query()
            ->where('locale', $locale)
            ->whereNull('user_id')
            ->whereHas('product', static function ($product) use ($label) {
                $product->where('source', Product::SOURCE_LABEL);
                foreach (['calories', 'proteins', 'fats', 'carbohydrates'] as $fact) {
                    $product->whereBetween($fact, [$label[$fact] - 0.5, $label[$fact] + 0.5]);
                }
            })
            ->limit(20)
            ->get();

        return $translations->first(static fn (ProductTranslation $translation) => array_intersect(
            FoodName::parse((string) $translation->name)->food,
            $food
        ) !== []);
    }

    /**
     * Store the food as a product with a translation per language: shared, or
     * the owner's own.
     *
     * @param  array{names: array<string, string>, calories: float, proteins: float, fats: float, carbohydrates: float, fibers: float, sugars?: float|null, glycemic_index?: int|null, nova?: int|null}  $facts
     * @return array<string, mixed> the hit of the translation in the locale
     */
    private function store(array $facts, string $locale, string $source = Product::SOURCE_AI, ?int $ownerId = null): array
    {
        $translation = DB::transaction(function () use ($facts, $locale, $source, $ownerId) {
            $quality = array_intersect_key($facts, array_flip(ProductQuality::FIELDS));

            $product = Product::create([
                'user_id' => $ownerId,
                'source' => $source,
                'calories' => $facts['calories'],
                'proteins' => $facts['proteins'],
                'fats' => $facts['fats'],
                'carbohydrates' => $facts['carbohydrates'],
                'fibers' => $facts['fibers'],
                'quality_source' => $quality !== [] ? $source : null,
            ] + $quality);

            $translations = [];
            foreach ($facts['names'] as $code => $name) {
                $translations[$code] = ProductTranslation::create([
                    'product_id' => $product->id,
                    'locale' => $code,
                    'name' => $name,
                    'user_id' => $ownerId,
                    'active' => 1,
                    'verified' => 0,
                ]);
            }

            return $translations[$locale] ?? $translations['ru'];
        });

        return self::hit($translation);
    }

    /**
     * A translation in the shape of a search hit.
     *
     * @return array<string, mixed>
     */
    private static function hit(ProductTranslation $translation): array
    {
        return [
            'id' => $translation->id,
            'product_id' => $translation->product_id,
            'locale' => $translation->locale,
            'name' => $translation->name,
            'user_id' => $translation->user_id,
            'active' => $translation->active,
            'verified' => $translation->verified,
        ];
    }

    private function languageName(string $locale): string
    {
        return match ($locale) {
            'ua' => 'Ukrainian',
            'en' => 'English',
            default => 'Russian',
        };
    }
}
