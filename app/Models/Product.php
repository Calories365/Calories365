<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    public static function getSearchedProductsViaMeili(string $encodedQuery, $count = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $locale = app()->getLocale();
        $user_id = auth()->id();

        return ProductTranslation::search($encodedQuery)
            ->where('locale', $locale)
            ->query(function ($query) use ($user_id) {
                $query->where(function ($subQuery) use ($user_id) {
                    $subQuery->where('user_id', $user_id)
                        ->orWhereNull('user_id');
                });
            })
            ->paginate($count);

    }

    public static function createProduct($validatedData): Product
    {
        $validatedData['user_id'] = auth()->id();

        return Product::create($validatedData);
    }


}

