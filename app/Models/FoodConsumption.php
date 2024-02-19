<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
