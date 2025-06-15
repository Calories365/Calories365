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

        Log::info(print_r($results, true));

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
                'limit' => 1,
            ]);

            //            Log::info('res: ');
            //            Log::info(print_r($res, true));
            $hits = $res->getHits();
            if (empty($hits)) {
                Log::info('Meili raw: no products');

                return false;
            }

            usort($hits, function ($a, $b) use ($user_id) {
                $scoreA = $a['_rankingScore'] ?? 0;
                $scoreB = $b['_rankingScore'] ?? 0;

                if ($scoreA !== $scoreB) {
                    return $scoreB <=> $scoreA;
                }

                $isOwnA = isset($a['user_id']) && $a['user_id'] == $user_id;
                $isOwnB = isset($b['user_id']) && $b['user_id'] == $user_id;

                return $isOwnB <=> $isOwnA;
            });

            $best = $hits[0];
            //            foreach ($hits as $hit) {
            //                if (mb_strtolower(trim($hit['name'])) === mb_strtolower(trim($query))) {
            //                    $best = $hit;
            //                    break;
            //                }
            //            }
            $ranking = $best['_rankingScore'] ?? 0;

            Log::info('$best: ');
            Log::info(print_r($best, true));

            return $ranking ? $best : false;

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
