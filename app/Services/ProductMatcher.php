<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Finds the database product for each food a user named.
 *
 * The search engine matches words, not meaning: "Нога куриная запечённая" finds
 * nothing because the database says "Куриная ножка", and "Гречка варёная" lands
 * on "гречка вареная с луком и морковью". A cooking method changes the nutrition
 * facts several times over, so a near match is worse than none: an item without
 * an exact match gets its facts generated for the full name instead.
 *
 * One request to the model writes the names a food database would use for each
 * item; the candidates found for all of them go to a second request, which picks
 * the same food with the same cooking method and nothing added, or nothing. Both
 * requests cover all items of a meal at once. When the model is unavailable, the
 * old rule applies: the best hit scoring at least 0.6.
 */
class ProductMatcher
{
    private const CANDIDATES_PER_QUERY = 5;

    private const CANDIDATES_PER_ITEM = 12;

    private const FALLBACK_MIN_SCORE = 0.6;

    /**
     * @param  array<int, string>  $names  the foods as the user named them
     * @return array<int, array<string, mixed>|null> per name: the chosen product translation hit, or null
     */
    public function match(array $names, ?int $userId, string $locale): array
    {
        $names = array_values($names);

        if ($names === []) {
            return [];
        }

        try {
            $queries = $this->searchQueries($names, $locale);
            $candidates = [];

            foreach ($names as $index => $name) {
                $candidates[$index] = $this->collectCandidates([$name, ...($queries[$index] ?? [])], $userId, $locale);
            }

            $choices = $this->choose($names, $candidates, $locale);

            return array_map(
                static fn (int $index) => $choices[$index] !== null ? ($candidates[$index][$choices[$index]] ?? null) : null,
                array_keys($names)
            );
        } catch (\Throwable $e) {
            Log::warning('Product matching by the model failed, using the search score', ['error' => $e->getMessage()]);

            return array_map(fn (string $name) => $this->bestByScore($name, $userId, $locale), $names);
        }
    }

    /**
     * Search hits for one query; the seam tests replace.
     *
     * @return array<int, array<string, mixed>>
     */
    public function candidates(string $query, ?int $userId, string $locale): array
    {
        return Product::searchCandidates($query, $userId, $locale, self::CANDIDATES_PER_QUERY);
    }

    /**
     * @param  array<int, string>  $queries
     * @return array<int, array<string, mixed>> hits keyed by translation id
     */
    private function collectCandidates(array $queries, ?int $userId, string $locale): array
    {
        $found = [];

        foreach (array_unique($queries) as $query) {
            foreach ($this->candidates($query, $userId, $locale) as $hit) {
                $found[(int) $hit['id']] ??= $hit;
            }

            if (count($found) >= self::CANDIDATES_PER_ITEM) {
                break;
            }
        }

        return array_slice($found, 0, self::CANDIDATES_PER_ITEM, true);
    }

    /**
     * Names a food database would use for each item, in the user's language.
     *
     * @param  array<int, string>  $names
     * @return array<int, array<int, string>>
     */
    private function searchQueries(array $names, string $locale): array
    {
        $lines = [];
        foreach ($names as $index => $name) {
            $lines[] = $index.'. '.$name;
        }

        $prompt = "A user of a calorie diary named these foods (language: {$this->languageName($locale)}):\n"
            .implode("\n", $lines)."\n\n"
            .'For each item write up to 3 short search queries, in the same language, the way the food is '
            .'usually named in a nutrition database: common word order, singular, usual synonyms '
            .'(for example "куриная ножка" and "окорочок" for "куриная нога"). Keep the cooking method and the '
            .'variety exactly as the user said them. When no cooking method was said and the food is eaten only '
            .'after cooking (grains, pasta, legumes, meat, fish), the weight is of the cooked food: name it in its '
            .'usual cooked form ("гречка варёная", "макароны отварные"). Otherwise add no cooking method.';

        $result = $this->ask($prompt, 'search_queries', [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['index', 'queries'],
                        'properties' => [
                            'index' => ['type' => 'integer'],
                            'queries' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                ],
            ],
        ]);

        $queries = [];
        foreach ($result['items'] ?? [] as $item) {
            $queries[(int) $item['index']] = array_slice(array_filter(array_map('trim', $item['queries'] ?? [])), 0, 3);
        }

        return $queries;
    }

    /**
     * The chosen translation id per item, or null when no candidate is the same food.
     *
     * @param  array<int, string>  $names
     * @param  array<int, array<int, array<string, mixed>>>  $candidates
     * @return array<int, int|null>
     */
    private function choose(array $names, array $candidates, string $locale): array
    {
        $choices = array_fill_keys(array_keys($names), null);

        $lines = [];
        foreach ($names as $index => $name) {
            if ($candidates[$index] === []) {
                continue;
            }

            $lines[] = "Item {$index}: {$name}";
            foreach ($candidates[$index] as $id => $hit) {
                $lines[] = "  {$id}: {$hit['name']}";
            }
        }

        if ($lines === []) {
            return $choices;
        }

        $prompt = "A user of a calorie diary named foods they ate (language: {$this->languageName($locale)}). "
            ."For each item pick the database product that is the same food, or null.\n\n"
            ."Rules:\n"
            .'- The cooking method must be the same: raw, boiled, fried, baked, grilled, stewed, steamed, '
            ."smoked and so on. Synonyms count as the same (\"отварной\" = \"варёный\", \"печёный\" = \"запечённый\").\n"
            .'- When the user named no cooking method: a food that is eaten only after cooking (grains, pasta, '
            .'legumes, meat, fish) was eaten cooked — boiled for grains and pasta. Never pick the dry or raw '
            ."product for it; pick the usual cooked one, or null. Anything else is as sold.\n"
            .'- No added ingredients, sauces or garnish the user did not name ("с луком", "с маслом", '
            ."\"со сметаной\"); a product that is only a brand or packaging variant of the same food is fine.\n"
            ."- The variety or fat content must match when the user named it.\n"
            .'- When no candidate fits, answer null: the nutrition facts are then estimated for the exact '
            ."name, which is better than a different food.\n\n"
            .implode("\n", $lines);

        $result = $this->ask($prompt, 'product_choice', [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['index', 'candidate_id'],
                        'properties' => [
                            'index' => ['type' => 'integer'],
                            'candidate_id' => ['type' => ['integer', 'null']],
                        ],
                    ],
                ],
            ],
        ]);

        foreach ($result['items'] ?? [] as $item) {
            $index = (int) $item['index'];
            $id = $item['candidate_id'];

            // Only an id that was offered for this item counts.
            if (array_key_exists($index, $choices) && $id !== null && isset($candidates[$index][(int) $id])) {
                $choices[$index] = (int) $id;
            }
        }

        return $choices;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function bestByScore(string $name, ?int $userId, string $locale): ?array
    {
        $best = $this->candidates($name, $userId, $locale)[0] ?? null;

        return $best && ($best['_rankingScore'] ?? 0) >= self::FALLBACK_MIN_SCORE ? $best : null;
    }

    /**
     * One structured answer of the matching model.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     *
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \RuntimeException
     */
    private function ask(string $prompt, string $name, array $schema): array
    {
        $keys = config('services.openai.keys');

        $result = Http::timeout(30)
            ->withToken((string) ($keys[app()->getLocale()] ?? $keys['ru'] ?? ''))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.models.matching'),
                'reasoning_effort' => config('services.openai.reasoning_effort'),
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => ['name' => $name, 'strict' => true, 'schema' => $schema],
                ],
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ])
            ->throw()
            ->json();

        $decoded = json_decode((string) ($result['choices'][0]['message']['content'] ?? ''), true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('The matching model gave no JSON');
        }

        return $decoded;
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
