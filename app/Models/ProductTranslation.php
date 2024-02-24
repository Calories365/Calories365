<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'locale', 'name', 'double_metaphoned_name', 'transliterated_name'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
