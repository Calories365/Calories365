<?php

namespace App\Models;

use App\Services\ProductQuality;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A food of a meal draft with the nutrition facts per 100 g it shows.
 *
 * @property int $id
 * @property int $meal_draft_id
 * @property int $position
 * @property string $name
 * @property int|null $product_id
 * @property int $quantity grams eaten
 * @property float $calories
 * @property float $proteins
 * @property float $fats
 * @property float $carbohydrates
 * @property float $fibers
 * @property string $source
 * @property bool $edited the user changed the facts or the name
 */
class MealDraftItem extends Model
{
    /**
     * A product of the database: found, or added by ProductCatalog.
     */
    public const SOURCE_DATABASE = 'database';

    /**
     * A product read from the photo of its label.
     */
    public const SOURCE_LABEL = 'label';

    /**
     * A food the user gave the nutrition facts of; it has no product until saved.
     */
    public const SOURCE_USER = 'user';

    public const FACTS = ['calories', 'proteins', 'fats', 'carbohydrates', 'fibers'];

    protected $fillable = [
        'meal_draft_id', 'position', 'name', 'product_id', 'quantity',
        'calories', 'proteins', 'fats', 'carbohydrates', 'fibers', 'source', 'edited',
    ];

    protected $casts = [
        'edited' => 'boolean',
        'calories' => 'float',
        'proteins' => 'float',
        'fats' => 'float',
        'carbohydrates' => 'float',
        'fibers' => 'float',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Product, $this>
     */
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(int $number): array
    {
        $per100 = [];
        $portion = [];
        foreach (self::FACTS as $fact) {
            $per100[$fact] = (float) $this->{$fact};
            $portion[$fact] = round($this->{$fact} * $this->quantity / 100, 1);
        }

        return [
            'number' => $number,
            'id' => $this->id,
            // Some products of the database are named in lower case: "мед різнотрав'я".
            'name' => Str::ucfirst((string) $this->name),
            'grams' => $this->quantity,
            'product_id' => $this->product_id,
            'source' => $this->source,
            'edited' => $this->edited,
            'per_100g' => $per100,
            'portion' => $portion,
            'quality' => $this->quality($portion['carbohydrates']),
        ];
    }

    /**
     * Sugars of the portion, the GI and the glycemic load of the portion, the
     * NOVA group and the marks the draft shows, from the item's product: nulls
     * without a product, or before the product has got them.
     *
     * @return array{sugars: float|null, glycemic_index: int|null, glycemic_load: float|null, nova: int|null, fast_carbohydrates: bool, ultra_processed: bool}
     */
    private function quality(float $carbohydrates): array
    {
        $product = $this->product_id !== null ? $this->product : null;
        $index = $product?->glycemic_index;

        return [
            'sugars' => $product?->sugars === null ? null : round($product->sugars * $this->quantity / 100, 1),
            'glycemic_index' => $index,
            'glycemic_load' => ProductQuality::glycemicLoad($index, $carbohydrates),
            'nova' => $product?->nova,
            'fast_carbohydrates' => ProductQuality::fastCarbohydrates($index, $carbohydrates),
            'ultra_processed' => $product?->nova === ProductQuality::ULTRA_PROCESSED,
        ];
    }
}
