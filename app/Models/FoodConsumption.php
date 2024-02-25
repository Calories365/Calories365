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
    ]; // Указание полей, которые можно массово назначать

    // Определение связи с моделью User

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Определение связи с моделью Product (еда)
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class, 'food_id');
    }

    public static function getMealsWithCurrentDate($date, $userId, $locale): \Illuminate\Database\Eloquent\Collection|array
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
            ->get();
    }

    public static function getDailyCaloriesSum($userId, $date): \Illuminate\Database\Eloquent\Collection|array
    {
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));

        return self::where('user_id', $userId)
            ->join('products', 'food_consumptions.food_id', '=', 'products.id')
            ->whereYear('consumed_at', $year)
            ->whereMonth('consumed_at', $month)
            ->groupBy(DB::raw('Date(consumed_at)'))
            ->select(DB::raw('Date(consumed_at) as date'), DB::raw('ROUND(SUM(food_consumptions.quantity * products.calories / 100)) as total_calories'))
            ->get()
            ->toArray();
    }

    public static function createFoodConsumption($product, $validatedData): FoodConsumption
    {
        $consumption = new FoodConsumption([
            'user_id' => auth()->id(),
            'food_id' => $product->id,
            'quantity' => $validatedData['quantity'],
            'part_of_day' => $validatedData['part_of_day'],
            'consumed_at' => $validatedData['consumed_at'],
        ]);

        $consumption->save();

        return $consumption;
    }

}
