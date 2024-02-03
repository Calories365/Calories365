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
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Определение связи с моделью Product (еда)
    public function product()
    {
        return $this->belongsTo(Product::class, 'food_id');
    }
}
