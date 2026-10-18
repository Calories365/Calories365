<?php

namespace App\Services;

/**
 * What kind of carbohydrates a food gives and how processed it is: sugars per
 * 100 g, the glycemic index (GI, glucose 100) and the NOVA group. The model
 * gives them with the nutrition facts (ProductCatalog, a label, ProductEnricher);
 * the rules of what it is asked and what is kept live here, and so does what
 * the draft shows from them.
 *
 * The glycemic load of a portion, GI × its carbohydrates / 100, says how much
 * a portion raises blood sugar: buckwheat and white bread with the same
 * carbohydrates differ about twofold.
 */
class ProductQuality
{
    public const FIELDS = ['sugars', 'glycemic_index', 'nova'];

    /**
     * NOVA group of ultra-processed foods.
     */
    public const ULTRA_PROCESSED = 4;

    /**
     * GI from which carbohydrates count as fast.
     */
    public const HIGH_GLYCEMIC_INDEX = 70;

    /**
     * Carbohydrates of a portion below which its GI says little.
     */
    public const NOTABLE_CARBOHYDRATES = 10;

    /**
     * Glycemic load of a meal from which it is medium, and high: a day under
     * about 80 counts as low and over 120 as high, and a meal is about a third.
     */
    public const MEDIUM_MEAL_LOAD = 25;

    public const HIGH_MEAL_LOAD = 40;

    /**
     * What the model is told about the fields, for any prompt that asks for them.
     */
    public static function instructions(): string
    {
        return '- sugars (g per 100 g): all sugars, natural and added, the part of the carbohydrates that is '
            ."sugar; 0 for meat, fish, eggs, oil.\n"
            .'- glycemic_index: of the food as eaten, glucose 100, from published GI tables or the closest food in '
            ."them; 0 when the food has under 2 g of carbohydrates per 100 g.\n"
            .'- nova: the NOVA group. 1: unprocessed or minimally processed, cooked at home (fresh, frozen, dried, '
            .'boiled, baked; plain milk, kefir, eggs, meat, fish, grains, legumes, nuts). 2: culinary ingredients '
            .'(oil, butter, sugar, honey, salt, flour). 3: processed foods made from a few of those (cheese, '
            .'canned vegetables, bread of flour, water and salt, smoked fish). 4: ultra-processed industrial foods '
            .'(packaged bread and buns, sausages, sweets, cookies, chips, sweet drinks, flavoured yogurts and '
            .'curd snacks, ready meals, fast food).';
    }

    /**
     * The fields in a JSON schema of the model's answer.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function schema(): array
    {
        return [
            'sugars' => ['type' => ['number', 'null']],
            'glycemic_index' => ['type' => ['integer', 'null']],
            'nova' => ['type' => ['integer', 'null']],
        ];
    }

    /**
     * The fields as stored: a value that cannot be true is null. Sugars never
     * exceed the carbohydrates, and a food almost without them has GI 0.
     *
     * @param  array<string, mixed>  $item
     * @return array{sugars: float|null, glycemic_index: int|null, nova: int|null}
     */
    public static function valid(array $item, float $carbohydrates): array
    {
        $sugars = $item['sugars'] ?? null;
        $sugars = is_numeric($sugars) && $sugars >= 0 && $sugars <= 100 ? round(min((float) $sugars, $carbohydrates), 2) : null;

        $index = $item['glycemic_index'] ?? null;
        $index = is_numeric($index) && $index >= 0 && $index <= 110 ? (int) round((float) $index) : null;
        if ($index !== null && $carbohydrates < 2) {
            $index = 0;
        }

        $nova = $item['nova'] ?? null;
        $nova = is_numeric($nova) && in_array((int) $nova, [1, 2, 3, 4], true) ? (int) $nova : null;

        return ['sugars' => $sugars, 'glycemic_index' => $index, 'nova' => $nova];
    }

    /**
     * The glycemic load of a portion, or null without a GI.
     */
    public static function glycemicLoad(?int $glycemicIndex, float $carbohydrates): ?float
    {
        return $glycemicIndex === null ? null : round($glycemicIndex * $carbohydrates / 100, 1);
    }

    /**
     * Whether a portion gives fast carbohydrates worth a mark.
     */
    public static function fastCarbohydrates(?int $glycemicIndex, float $carbohydrates): bool
    {
        return $glycemicIndex !== null
            && $glycemicIndex >= self::HIGH_GLYCEMIC_INDEX
            && $carbohydrates >= self::NOTABLE_CARBOHYDRATES;
    }

    /**
     * low, medium or high, for the glycemic load of a meal.
     */
    public static function mealLoadLevel(float $load): string
    {
        return match (true) {
            $load >= self::HIGH_MEAL_LOAD => 'high',
            $load >= self::MEDIUM_MEAL_LOAD => 'medium',
            default => 'low',
        };
    }
}
