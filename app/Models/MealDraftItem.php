<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
            'name' => $this->name,
            'grams' => $this->quantity,
            'product_id' => $this->product_id,
            'source' => $this->source,
            'edited' => $this->edited,
            'per_100g' => $per100,
            'portion' => $portion,
        ];
    }
}
