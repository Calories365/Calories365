<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class ProductTranslation extends Model
{
    use HasFactory;
    use searchable;

    public const SORTABLE = ['id'];
    public const FILTERABLE = ['locale', 'user_id','active'];
    protected $fillable = [
        'product_id', 'locale', 'name', 'double_metaphoned_name', 'transliterated_name','user_id','active'
    ];
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function createProductTranslations($product, $validatedData): bool
    {
        $supportedLocales = config('app.supported_locales', []);

        foreach ($supportedLocales as $locale) {
            $dataForTranslation = [
                'product_id' => $product->id,
                'locale' => $locale,
                'name' => $validatedData['name'],
                'user_id' => $validatedData['user_id'],
                'active' => $validatedData['active'],
            ];

            ProductTranslation::create($dataForTranslation);
        }

        return true;
    }


    public function toSearchableArray()
    {
        $data = [
            'locale' => $this->locale,
            'product_id' => $this->product_id,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'active' => $this->active,
        ];
        return $data;
    }

    public function searchableAs()
    {
        return 'products';
    }

}
