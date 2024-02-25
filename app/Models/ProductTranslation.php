<?php

namespace App\Models;

use DoubleMetaphone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use voku\helper\ASCII;

class ProductTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'locale', 'name', 'double_metaphoned_name', 'transliterated_name'
    ];
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function createProductTranslation($product, $validatedData): ProductTranslation
    {
        $transiltedData = ASCII::to_transliterate($validatedData['name']);
        $doubleMetaphoneName = new DoubleMetaphone($transiltedData);
        $translation = new ProductTranslation([
            'product_id' => $product->id,
            'locale' => app()->getLocale(),
            'name' => $validatedData['name'],
            'transliterated_name' => $transiltedData,
            'double_metaphoned_name' => $doubleMetaphoneName->primary,
        ]);

        $translation->save();

        return $translation;
    }

}
