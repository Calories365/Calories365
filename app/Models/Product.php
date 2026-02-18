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
    ];

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

    public static function getRawProduct(string $query, ?int $user_id, string $locale): array|bool
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
                'limit' => 5,
            ]);

            $hits = $res->getHits();
            if (empty($hits)) {
                return false;
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

                if ($scoreA <> $scoreB) {
                    return $scoreB <=> $scoreA;
                }

                $isOwnA = isset($a['user_id']) && $a['user_id'] == $user_id;
                $isOwnB = isset($b['user_id']) && $b['user_id'] == $user_id;

                return $isOwnB <=> $isOwnA;
            });

            $best = $hits[0];
            $best['_rankingScore'] = $best['_adjustedScore'];

            return ($best['_rankingScore'] ?? 0) > 0 ? $best : false;

        } catch (\Throwable $e) {
            Log::error('getRawProduct error: '.$e->getMessage(), [
                'query' => $query,
                'user' => $user_id,
                'loc' => $locale,
            ]);

            return false;
        }
    }

    public static function createProduct($validatedData): Product
    {
        $validatedData['user_id'] = $validatedData['user_id'] ?? auth()->id();

        return Product::create($validatedData);
    }
}
