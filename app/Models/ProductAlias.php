<?php

namespace App\Models;

use App\Services\FoodName;
use Illuminate\Database\Eloquent\Model;

/**
 * A said name ProductCatalog tied to a shared product, see the migration.
 */
class ProductAlias extends Model
{
    protected $fillable = ['locale', 'name', 'product_id'];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The key a said name is kept by: its cooking method and stems, so
     * "Нога куриная запечённая" and "куриные ноги запечённые" are one alias.
     * Null for a name without a word to keep.
     */
    public static function keyFor(string $name): ?string
    {
        $food = FoodName::parse($name);

        return $food->searchText() === '' ? null : mb_substr($food->cooking.':'.$food->searchText(), 0, 191);
    }

    /**
     * Translations in the locale of the shared products kept for the names.
     *
     * @param  array<int, string>  $names
     * @return array<int, ProductTranslation> keyed like the names; a name without an alias is left out
     */
    public static function translationsFor(array $names, string $locale): array
    {
        $keys = array_filter(array_map(static fn (string $name) => self::keyFor($name), $names));

        if ($keys === []) {
            return [];
        }

        $productIds = self::query()
            ->where('locale', $locale)
            ->whereIn('name', array_unique($keys))
            ->pluck('product_id', 'name');

        $translations = ProductTranslation::query()
            ->whereIn('product_id', $productIds->values())
            ->where('locale', $locale)
            ->whereNull('user_id')
            ->get()
            ->keyBy('product_id');

        $found = [];
        foreach ($keys as $index => $key) {
            $translation = $translations[$productIds[$key] ?? 0] ?? null;
            if ($translation) {
                $found[$index] = $translation;
            }
        }

        return $found;
    }

    /**
     * Keep the said name for the product.
     */
    public static function remember(string $name, string $locale, int $productId): void
    {
        $key = self::keyFor($name);

        if ($key !== null) {
            self::query()->updateOrCreate(['locale' => $locale, 'name' => $key], ['product_id' => $productId]);
        }
    }
}
