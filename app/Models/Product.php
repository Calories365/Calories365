<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Meilisearch\Client;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'calories',
        'proteins',
        'carbohydrates',
        'fats',
        'fibers',
        'is_popular',
        'user_id',
        'source',
    ];

    /**
     * Products the model estimated the nutrition facts of, see ProductCatalog.
     */
    public const SOURCE_AI = 'ai';

    /**
     * Products with the nutrition facts printed on their label, read from a photo.
     */
    public const SOURCE_LABEL = 'label';

    public function translations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public static function getPopularProducts($cacheKey)
    {
        return Cache::remember($cacheKey, now()->addMinutes(1440), function () {
            $locale = app()->getLocale();

            return self::with(['translations' => function ($query) use ($locale) {
                $query->where('locale', $locale);
            }])
                ->where('is_popular', true)
                ->get();
        });
    }

    public static function getSearchedProductsViaMeili(
        string $encodedQuery,
        bool $paginate = true,
        int $count = 10
    ): LengthAwarePaginator|Collection {

        $locale = app()->getLocale();
        $user_id = auth()->id();

        /**
         * 1) user_id = $user_id, active=1, verified ∈ [0,1]
         * 2) user_id IS NULL
         * 3) verified=1 AND user_id != $user_id
         */
        $builder = ProductTranslation::search($encodedQuery)
            ->where('locale', $locale)
            ->query(function ($query) use ($user_id) {
                $query->where(function ($subQuery) use ($user_id) {
                    $subQuery
                        ->where(function ($inner) use ($user_id) {
                            $inner->where('user_id', $user_id)
                                ->where('active', 1)
                                ->whereIn('verified', [0, 1]);
                        })
                        ->orWhereNull('user_id')
                        ->orWhere(function ($inner) use ($user_id) {
                            $inner->where('verified', 1)
                                ->where('user_id', '!=', $user_id);
                        });
                });
            });

        $results = $builder->take(30)->get();

        /**
         * Sort the resulting collection so that
         *  goods of the current user (user_id = $user_id) go first.
         */
        $sortedResults = $results->sortByDesc(function ($product) use ($user_id) {
            return $product->user_id == $user_id ? 1 : 0;
        })->values();

        if ($paginate) {
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $perPage = $count;
            $total = $sortedResults->count();

            $items = $sortedResults
                ->slice(($currentPage - 1) * $perPage, $perPage)
                ->values();

            return new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );
        } else {
            return $sortedResults->take($count);
        }
    }

    /**
     * The best product visible to the user for the query, or false.
     */
    public static function getRawProduct(string $query, ?int $user_id, string $locale): array|bool
    {
        $best = self::searchCandidates($query, $user_id, $locale)[0] ?? null;

        return $best && $best['_rankingScore'] > 0 ? $best : false;
    }

    /**
     * What ProductMatcher checks and ranks found products by: the nutrition facts,
     * how often each was eaten, by anyone and by the user, and where the facts
     * came from. Products without calories are left out.
     *
     * @param  array<int, int>  $ids
     * @return array<int, array{calories: float, proteins: float, fats: float, carbohydrates: float, uses: int, mine: int, source: string|null}> keyed by product id
     */
    public static function matchingFacts(array $ids, ?int $userId): array
    {
        if ($ids === []) {
            return [];
        }

        return self::query()
            ->whereIn('products.id', $ids)
            ->whereNotNull('products.calories')
            ->leftJoin('food_consumptions', 'food_consumptions.food_id', '=', 'products.id')
            ->groupBy('products.id', 'products.source', 'products.calories', 'products.proteins', 'products.fats', 'products.carbohydrates')
            ->selectRaw(
                'products.id, products.source, products.calories, products.proteins, products.fats, products.carbohydrates, '
                .'COUNT(food_consumptions.id) AS uses, COALESCE(SUM(food_consumptions.user_id = ?), 0) AS mine',
                [$userId ?? 0]
            )
            ->toBase()
            ->get()
            ->mapWithKeys(static fn (object $row) => [(int) $row->id => [
                'calories' => (float) $row->calories,
                'proteins' => (float) $row->proteins,
                'fats' => (float) $row->fats,
                'carbohydrates' => (float) $row->carbohydrates,
                'uses' => (int) $row->uses,
                'mine' => (int) $row->mine,
                'source' => $row->source,
            ]])
            ->all();
    }

    /**
     * Products visible to the user whose name matches the query, best first.
     *
     * Each hit is a product translation from the Meilisearch index; _rankingScore
     * is the engine's score adjusted below. An empty list when nothing matches or
     * the search fails.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function searchCandidates(string $query, ?int $user_id, string $locale, int $limit = 5): array
    {
        $client = new Client(env('MEILISEARCH_HOST'), env('MEILISEARCH_KEY'));

        $parts = [];
        if ($user_id !== null) {
            $parts[] = "(user_id = {$user_id} AND active = 1 AND verified IN [0,1])";
        }
        $parts[] = '(user_id IS NULL)';
        $parts[] = $user_id !== null
            ? "(verified = 1 AND user_id != {$user_id})"
            : '(verified = 1)';
        $filter = "locale = '{$locale}' AND (".implode(' OR ', $parts).')';

        try {
            $res = $client->index('products')->search($query, [
                'showRankingScore' => true,
                'filter' => $filter,
                'limit' => $limit,
            ]);

            $hits = $res->getHits();
            if (empty($hits)) {
                return [];
            }

            $queryNorm = mb_strtolower(trim($query));
            $queryWordCount = count(preg_split('/\s+/', $queryNorm, -1, PREG_SPLIT_NO_EMPTY));

            foreach ($hits as &$hit) {
                $nameNorm = mb_strtolower(trim($hit['name']));
                $meiliScore = $hit['_rankingScore'] ?? 0;

                if ($nameNorm === $queryNorm) {
                    $hit['_adjustedScore'] = $meiliScore + 0.1;
                } else {
                    $nameWordCount = count(preg_split('/\s+/', $nameNorm, -1, PREG_SPLIT_NO_EMPTY));

                    if ($queryWordCount === 1 && $nameWordCount > 1) {
                        $wordRatio = 1.0 / max($nameWordCount, 1);
                        $hit['_adjustedScore'] = $meiliScore * $wordRatio;
                    } else {
                        $hit['_adjustedScore'] = $meiliScore;
                    }
                }
            }
            unset($hit);

            usort($hits, function ($a, $b) use ($user_id) {
                $scoreA = $a['_adjustedScore'] ?? 0;
                $scoreB = $b['_adjustedScore'] ?? 0;

                if ($scoreA != $scoreB) {
                    return $scoreB <=> $scoreA;
                }

                $isOwnA = isset($a['user_id']) && $a['user_id'] == $user_id;
                $isOwnB = isset($b['user_id']) && $b['user_id'] == $user_id;

                return $isOwnB <=> $isOwnA;
            });

            return array_map(static function (array $hit): array {
                $hit['_rankingScore'] = $hit['_adjustedScore'];

                return $hit;
            }, $hits);
        } catch (\Throwable $e) {
            Log::error('searchCandidates error: '.$e->getMessage(), [
                'query' => $query,
                'user' => $user_id,
                'loc' => $locale,
            ]);

            return [];
        }
    }

    public static function createProduct($validatedData): Product
    {
        $validatedData['user_id'] = $validatedData['user_id'] ?? auth()->id();

        return Product::create($validatedData);
    }
}
