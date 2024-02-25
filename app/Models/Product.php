<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ukr',
        'name_rus',
        'name_eng',
        'calories',
        'proteins',
        'carbohydrates',
        'fats',
        'fibers',
        'is_popular',
        'user'
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

    public static function getSearchedProducts($encodedQuery): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $locale = app()->getLocale();
        $user_id = auth()->id();

        return DB::table('products')
            ->join('product_translations', 'products.id', '=', 'product_translations.product_id')
            ->where('product_translations.locale', '=', $locale)
            ->where('product_translations.double_metaphoned_name', 'LIKE', "%{$encodedQuery}%")
            ->where(function ($query) use ($user_id) {
                $query->where('products.user', '=', $user_id)
                    ->orWhereNull('products.user');
            })
            ->select(
                'products.id',
                'products.calories',
                'products.proteins',
                'products.carbohydrates',
                'products.fats',
                'products.fibers',
                'products.user',
                'product_translations.name as name'
            )
            ->orderByRaw("
        CASE
            WHEN double_metaphoned_name = ? THEN 1
            WHEN double_metaphoned_name LIKE ? THEN 2
            ELSE 3
        END,
        CASE
            WHEN double_metaphoned_name LIKE ? THEN ABS(LENGTH(double_metaphoned_name) - LENGTH(?))
            ELSE 9999
        END,
        double_metaphoned_name ASC
    ", [$encodedQuery, "%{$encodedQuery}%", "%{$encodedQuery}%", $encodedQuery])
            ->paginate(10);
    }

    public static function createProduct($validatedData): Product
    {
        $validatedData['user_id'] = auth()->id();

        return Product::create($validatedData);
    }


}

