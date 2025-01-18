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

        $locale  = app()->getLocale();
        $user_id = auth()->id();

        /**
         * Итоговые условия:
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
         * Сортируем полученную коллекцию так, чтобы
         * товары текущего пользователя (user_id = $user_id) шли первыми.
         */
        $sortedResults = $results->sortByDesc(function ($product) use ($user_id) {
            return $product->user_id == $user_id ? 1 : 0;
        })->values();

        if ($paginate) {
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $perPage     = $count;
            $total       = $sortedResults->count();

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



    public static function getRawProduct($query, $user_id, $locale): array|bool
    {
        $client = new Client(env('MEILISEARCH_HOST'), env('MEILISEARCH_KEY'));

        $filters = "active = 1 AND locale = '{$locale}' AND (user_id = {$user_id} OR user_id IS NULL)";

        $res = $client->index('products')->search($query, [
            'showRankingScore' => true,
            'limit' => 2,
            'filter' => $filters,
        ]);

        $hits = $res->getHits();

        if (!empty($hits))
        {
            if($hits[0]['_rankingScore'] == $hits[1]['_rankingScore'])
            {
                if ($hits[0]['user_id'])
                {
                    $firstProduct = $hits[0];
                } else
                {
                    $firstProduct = $hits[1];
                }
            } else {
                $firstProduct = $hits[0];
            }

            $rankingScore = $firstProduct['_rankingScore'];

            if ($rankingScore) {
                return $firstProduct;
            } else {
                return false;
            }
        } else {
            Log::info("No products found.");
            return false;
        }
    }

    public static function createProduct($validatedData): Product
    {
        $validatedData['user_id'] = $validatedData['user_id'] ?? auth()->id();
        return Product::create($validatedData);
    }


}

