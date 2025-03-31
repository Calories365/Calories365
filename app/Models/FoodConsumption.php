<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        // Добавляем логирование для отладки
        Log::info('FoodConsumption::createFoodConsumption data', $validatedData);
        
        // Проверяем есть ли product, если нет, то должен быть food_id
        if ($product !== null) {
            $validatedData['food_id'] = $product->id;
        } elseif (!isset($validatedData['food_id']) && isset($validatedData['product_id'])) {
            $validatedData['food_id'] = $validatedData['product_id'];
        }
        
        // Проверяем и устанавливаем consumed_at, если не указано
        if (!isset($validatedData['consumed_at'])) {
            $validatedData['consumed_at'] = now();
        }
        
        // Проверяем, есть ли значение quantity
        if (!isset($validatedData['quantity']) && isset($validatedData['quantity_grams'])) {
            $validatedData['quantity'] = $validatedData['quantity_grams'];
        }
        
        // Преобразование русских названий частей дня в английские
        $mealTypeMap = [
            'завтрак' => 'morning',
            'обед' => 'dinner',
            'ужин' => 'supper'
        ];
        
        if (isset($validatedData['part_of_day']) && isset($mealTypeMap[$validatedData['part_of_day']])) {
            $validatedData['part_of_day'] = $mealTypeMap[$validatedData['part_of_day']];
            Log::info('Part of day converted to English in Model::createFoodConsumption', 
                ['converted' => $validatedData['part_of_day']]);
        }
        
        // Устанавливаем дефолтные значения
        $validatedData['quantity'] = $validatedData['quantity'] ?? 0;
        $validatedData['part_of_day'] = $validatedData['part_of_day'] ?? 'morning';
        
        // Создаем запись
        return FoodConsumption::create($validatedData);
    }


}
