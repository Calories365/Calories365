<?php

namespace App\Models;

use App\Services\FoodName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class ProductTranslation extends Model
{
    use HasFactory;
    use searchable;

    public const SORTABLE = ['id'];

    public const FILTERABLE = ['locale', 'user_id', 'active', 'verified', 'cooking'];

    /**
     * The site searches the name as written; ProductMatcher searches the stems.
     */
    public const SEARCHABLE = ['name', 'stems'];

    protected $fillable = [
        'product_id', 'locale', 'name', 'double_metaphoned_name', 'transliterated_name', 'user_id', 'active', 'verified',
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Names of shared products written in one word: "Грибы", "Фасоль".
     *
     * @return array<int, string>
     */
    public static function oneWordNames(): array
    {
        return self::query()
            ->whereNull('user_id')
            ->where('name', 'not like', '% %')
            ->distinct()
            ->pluck('name')
            ->all();
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
                'verified' => $validatedData['verified'],
            ];

            ProductTranslation::create($dataForTranslation);
        }

        return true;
    }

    /**
     * The document of the products index.
     *
     * Stems and cooking come from FoodName, the rules ProductMatcher reads a
     * spoken name by: a product is found by the stems of its food and filtered by
     * how it was cooked.
     */
    public function toSearchableArray()
    {
        $name = FoodName::parse((string) $this->name);

        return [
            'locale' => $this->locale,
            'product_id' => $this->product_id,
            'name' => $this->name,
            'stems' => $name->searchText(),
            'cooking' => $name->cooking,
            'user_id' => $this->user_id,
            'active' => $this->active,
            'verified' => $this->verified,
        ];
    }

    public function searchableAs()
    {
        return 'products';
    }
}
