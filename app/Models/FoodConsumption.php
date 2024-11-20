<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FoodConsumption extends Model
{
    use HasFactory;


    /**
     * @var int|mixed|string|null
     */
    protected $fillable = [
        'user_id', 'food_id', 'quantity', 'consumed_at', 'part_of_day'
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class, 'food_id');
    }

    public static function getMealsWithCurrentDate($date, $userId, $locale, $partOfDay = false): \Illuminate\Database\Eloquent\Collection|array
    {
        return self::with(['product' => function ($query) use ($locale) {
            $query->select('id', 'calories', 'proteins', 'carbohydrates', 'fats', 'fibers')
                ->with(['translations' => function ($query) use ($locale) {
                    $query->where('locale', $locale)
                        ->select('product_id', 'name', 'locale');
                }]);
        }])
            ->where('user_id', $userId)
            ->whereDate('consumed_at', $date)
            ->when($partOfDay, function ($query) use ($partOfDay) {
                return $query->where('part_of_day', $partOfDay);
            })
            ->get();
    }



    public static function getDailyCaloriesSum($userId, $date): \Illuminate\Database\Eloquent\Collection|array
    {
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));

        return self::where('food_consumptions.user_id', $userId)
            ->join('products', 'food_consumptions.food_id', '=', 'products.id')
            ->whereYear('consumed_at', $year)
            ->whereMonth('consumed_at', $month)
            ->groupBy(DB::raw('Date(consumed_at)'))
            ->select(DB::raw('Date(consumed_at) as date'), DB::raw('ROUND(SUM(food_consumptions.quantity * products.calories / 100)) as total_calories'))
            ->get()
            ->toArray();
    }

    public static function createFoodConsumption($validatedData, $product = null): FoodConsumption
    {

        if ($product !== null) {
            $validatedData['food_id'] = $product->id;
        }

        return FoodConsumption::create($validatedData);
    }


}
