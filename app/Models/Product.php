<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        'user_id'
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

//    public static function getSearchedProductsViaMeili(
//        string $encodedQuery,
//        bool $paginate = true,
//        int $count = 10
//    ): LengthAwarePaginator|Collection {
//        $locale = 'ru';
////        $locale = app()->getLocale();
//        $user_id = auth()->id();
//
//        $builder = ProductTranslation::search($encodedQuery)
//            ->where('locale', $locale)
//            ->query(function ($query) use ($user_id) {
//                $query->where(function ($subQuery) use ($user_id) {
//                    $subQuery->where('user_id', $user_id)
//                        ->orWhereNull('user_id');
//                });
//            });
//
//        if ($paginate) {
//            return $builder->paginate();
//        } else {
//            return $builder->take($count)->get();
//        }
//    }
    public static function getSearchedProductsViaMeili(
        string $encodedQuery,
        bool $paginate = true,
        int $count = 10
    ): LengthAwarePaginator|Collection {
        $locale = 'ru';
        // $locale = app()->getLocale();
        $user_id = auth()->id();
        $user_id = 32;

        // Получаем больше результатов из Meilisearch
        $builder = ProductTranslation::search($encodedQuery)
            ->where('locale', $locale)
            ->query(function ($query) use ($user_id) {
                $query->where(function ($subQuery) use ($user_id) {
                    $subQuery->where('user_id', $user_id)
                        ->orWhereNull('user_id');
                });
            });

        // Получаем до 100 результатов
        $results = $builder->take(100)->get();

        // Сортируем результаты: сначала продукты текущего пользователя
        $sortedResults = $results->sortByDesc(function ($product) use ($user_id) {
            return $product->user_id == $user_id ? 1 : 0;
        })->values();

        if ($paginate) {
            // Пагинируем результаты вручную
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $perPage = $count;
            $total = $sortedResults->count();

            $items = $sortedResults->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $paginator = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            return $paginator;
        } else {
            return $sortedResults->take($count);
        }
    }


    public static function createProduct($validatedData): Product
    {
        $validatedData['user_id'] = $validatedData['user_id'] ?? auth()->id();
        Log::info(print_r($validatedData, true));


        return Product::create($validatedData);
    }


}

