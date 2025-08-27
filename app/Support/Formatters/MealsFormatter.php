<?php

declare(strict_types=1);

namespace App\Support\Formatters;

use Illuminate\Support\Collection;

final class MealsFormatter
{
    /**
     * Converts a collection/array of FoodConsumption to an array of strings
     *  of the form "Name — N g". Identical names are summed.
     *
     * @return array<int, string>
     */
    public static function toNameGramsList(Collection|array $meals): array
    {
        $list = ($meals instanceof Collection ? $meals : collect($meals))
            ->map(function ($item) {
                $product = is_array($item) ? ($item['product'] ?? null) : ($item->product ?? null);
                $translations = $product instanceof Collection
                    ? $product->getRelation('translations')
                    : ($product['translations'] ?? ($product->translations ?? null));

                $firstTranslation = null;
                if ($translations instanceof Collection) {
                    $firstTranslation = $translations->first();
                } elseif (is_array($translations)) {
                    $firstTranslation = reset($translations) ?: null;
                }

                $name =
                    (is_array($firstTranslation) ? ($firstTranslation['name'] ?? null) : null)
                    ?? ($firstTranslation->name ?? null)
                    ?? 'Без назви';

                $grams = (int) (is_array($item) ? ($item['quantity'] ?? 0) : ($item->quantity ?? 0));

                return ['name' => $name, 'grams' => $grams];
            })
            ->groupBy('name')
            ->map(fn (Collection $items) => (int) $items->sum('grams'))
            ->map(fn (int $grams, string $name) => $name.' — '.$grams.' г')
            ->values()
            ->all();

        return $list;
    }
}
