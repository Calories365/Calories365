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
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function createProductTranslations($product, $validatedData, $doubleMetaphoneName): bool
    {
        $supportedLocales = config('app.supported_locales', []);

        foreach ($supportedLocales as $locale) {
            $dataForTranslation = [
                'product_id' => $product->id,
                'locale' => $locale,
                'name' => $validatedData['name'],
                'transliterated_name' => null,
                'double_metaphoned_name' => $doubleMetaphoneName,
            ];

            ProductTranslation::create($dataForTranslation);
        }

        return true;
    }

}
