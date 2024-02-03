<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'is_popular'
    ];

    // Указываем связь с моделью ProductTranslation
    public function translations()
    {
        return $this->hasMany(ProductTranslation::class);
    }
}
