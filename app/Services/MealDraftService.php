<?php

namespace App\Services;

use App\Models\FoodConsumption;
use App\Models\MealDraft;
use App\Models\MealDraftItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The meal draft: a meal put together from several messages, voice, text, a
 * photo of the dish or of a label, in any order, and saved to the diary at once.
 *
 * Every input becomes operations on the draft (MealInputReader): add a food,
 * correct an item, remove one. A short edit, "2 250" or "убрать 3", needs no
 * model. The foods named get their products from ProductCatalog, all of one
 * input in one call; a label makes its own product. The draft keeps the facts
 * each item shows; nutrition facts the user tells mark the item edited, and it
 * is saved as the user's own product.
 */
class MealDraftService
{
    public function __construct(
        private MealInputReader $reader,
        private ProductCatalog $catalog,
        private ProductService $products,
    ) {}

    /**
     * Apply one input to the user's draft; understood is false when the input
     * says nothing about food, not_found lists the foods no product was found for.
     *
     * @param  array{mime: string, contents: string}|null  $photo
     * @return array{draft: MealDraft|null, understood: bool, not_found: array<int, string>}
     *
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \RuntimeException
     */
    public function input(int $userId, string $locale, ?string $text, ?array $photo): array
    {
        $draft = MealDraft::openFor($userId);
        $items = $draft ? $draft->items->values() : collect();

        $read = ($photo === null ? $this->shortEdit((string) $text, $items) : null)
            ?? $this->reader->read($items->map(static fn (MealDraftItem $item) => ['name' => $item->name, 'grams' => $item->quantity])->all(), $text, $photo, $locale);

        if ($read['operations'] === []) {
            return ['draft' => $draft, 'understood' => false, 'not_found' => []];
        }

        $products = $this->products($read, $userId, $locale);

        $notFound = DB::transaction(function () use (&$draft, $items, $read, $products, $userId) {
            $draft ??= MealDraft::create(['user_id' => $userId]);
            $notFound = [];
            $removed = [];

            foreach ($read['operations'] as $index => $operation) {
                $item = $operation['item'] !== null ? $items[$operation['item'] - 1] : null;
                $product = $products[$index] ?? null;

                if ($item !== null && isset($removed[$item->id])) {
                    continue;
                }

                if ($operation['action'] === 'remove') {
                    $item->delete();
                    $removed[$item->id] = true;

                    continue;
                }

                if ($operation['name'] !== '' || $operation['label'] !== null) {
                    if ($product === null && ! $this->toldAllFacts($operation)) {
                        $notFound[] = $operation['name'];

                        continue;
                    }

                    // A new item goes after the others until renumber() gives it its place.
                    $item ??= new MealDraftItem(['meal_draft_id' => $draft->id, 'position' => 1000 + $index]);
                    $item->fill($product !== null
                        ? $product + ['edited' => false]
                        : ['name' => $operation['name'], 'product_id' => null, 'fibers' => 0, 'source' => MealDraftItem::SOURCE_USER]);
                }

                if ($operation['facts'] !== []) {
                    $item->fill($operation['facts'] + ['edited' => true]);
                }

                if ($operation['grams'] !== null) {
                    $item->quantity = (int) round($operation['grams']);
                }

                $item->save();
            }

            $this->renumber($draft);

            return $notFound;
        });

        $draft = $draft->fresh();
        if ($draft->items->isEmpty()) {
            $draft->delete();
            $draft = null;
        }

        return ['draft' => $draft, 'understood' => true, 'not_found' => $notFound];
    }

    /**
     * Save the draft to the diary and close it.
     *
     * An item of a product the user did not change is a meal of that product;
     * an item the user changed becomes the user's own product (ProductService).
     *
     * @return array<string, float>|null the total of the meal, or null without a draft
     */
    public function save(int $userId, string $partOfDay, string $date): ?array
    {
        $draft = MealDraft::openFor($userId);

        if (! $draft || $draft->items->isEmpty()) {
            return null;
        }

        $total = $draft->toPayload()['total'];

        DB::transaction(function () use ($draft, $userId, $partOfDay, $date) {
            foreach ($draft->items as $item) {
                if ($item->product_id !== null && ! $item->edited) {
                    FoodConsumption::createFoodConsumption([
                        'user_id' => $userId,
                        'food_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'part_of_day' => $partOfDay,
                        'consumed_at' => $date,
                    ]);

                    continue;
                }

                $this->products->createProductWithTranslationsAndConsumption([
                    'user_id' => $userId,
                    'name' => $item->name,
                    'calories' => $item->calories,
                    'proteins' => $item->proteins,
                    'fats' => $item->fats,
                    'carbohydrates' => $item->carbohydrates,
                    'fibers' => $item->fibers,
                    'quantity' => $item->quantity,
                    'part_of_day' => $partOfDay,
                    'consumed_at' => $date,
                ]);
            }

            $draft->delete();
        });

        return $total;
    }

    public function cancel(int $userId): void
    {
        MealDraft::query()->where('user_id', $userId)->delete();
    }

    /**
     * "2 250" sets the grams of item 2, "убрать 3" removes item 3; anything else
     * goes to the model.
     *
     * @param  Collection<int, MealDraftItem>  $items
     * @return array{operations: array<int, array<string, mixed>>, labels: array<int, mixed>}|null
     */
    private function shortEdit(string $text, Collection $items): ?array
    {
        $text = mb_strtolower(trim($text));

        if (preg_match('/^(\d{1,2})\s*[-—:.]?\s*(\d{1,4})\s*(?:г|гр|грам\p{L}*|g)?\.?$/u', $text, $match)
            && isset($items[$match[1] - 1]) && $match[2] > 0) {
            return ['operations' => [['action' => 'update', 'item' => (int) $match[1], 'name' => '', 'grams' => (float) $match[2], 'label' => null, 'facts' => []]], 'labels' => []];
        }

        if (preg_match('/^(?:-|убра\p{L}*|убери|удал\p{L}*|прибра\p{L}*|прибери|видал\p{L}*|remove|delete)\s*(\d{1,2})$/u', $text, $match)
            && isset($items[$match[1] - 1])) {
            return ['operations' => [['action' => 'remove', 'item' => (int) $match[1], 'name' => '', 'grams' => null, 'label' => null, 'facts' => []]], 'labels' => []];
        }

        return null;
    }

    /**
     * The product and its facts for each operation that names a food: from a
     * label, or from ProductCatalog for all names at once.
     *
     * @param  array{operations: array<int, array<string, mixed>>, labels: array<int, mixed>}  $read
     * @return array<int, array<string, mixed>|null> item attributes per operation index; null when not found
     */
    private function products(array $read, int $userId, string $locale): array
    {
        $hits = [];
        $names = [];

        foreach ($read['operations'] as $index => $operation) {
            if ($operation['action'] === 'remove') {
                continue;
            }

            if ($operation['label'] !== null) {
                $hits[$index] = ['hit' => $this->catalog->addFromLabel($read['labels'][$operation['label']], $locale, $userId), 'source' => MealDraftItem::SOURCE_LABEL];
            } elseif ($operation['name'] !== '' && ! $this->toldAllFacts($operation)) {
                $names[$index] = $operation['name'];
            }
        }

        if ($names !== []) {
            $found = $this->catalog->find(array_values($names), $userId, $locale);
            foreach (array_keys($names) as $position => $index) {
                $hits[$index] = $found[$position] ? ['hit' => $found[$position], 'source' => MealDraftItem::SOURCE_DATABASE] : null;
            }
        }

        $facts = Product::query()
            ->whereIn('id', array_map(static fn (array $hit) => $hit['hit']['product_id'], array_filter($hits)))
            ->get()
            ->keyBy('id');

        $products = [];
        foreach ($hits as $index => $hit) {
            $product = $hit ? $facts[$hit['hit']['product_id']] ?? null : null;

            $products[$index] = $product === null ? null : [
                'name' => $hit['hit']['name'],
                'product_id' => $product->id,
                'calories' => $product->calories,
                'proteins' => $product->proteins,
                'fats' => $product->fats,
                'carbohydrates' => $product->carbohydrates,
                'fibers' => $product->fibers ?? 0,
                'source' => $hit['source'],
            ];
        }

        return $products;
    }

    /**
     * Whether the user told all the facts of a food: it needs no product then.
     *
     * @param  array<string, mixed>  $operation
     */
    private function toldAllFacts(array $operation): bool
    {
        return count($operation['facts']) === 4;
    }

    private function renumber(MealDraft $draft): void
    {
        $draft->items()->get()->sortBy([['position', 'asc'], ['id', 'asc']])->values()
            ->each(static function (MealDraftItem $item, int $index) {
                if ($item->position !== $index + 1) {
                    $item->update(['position' => $index + 1]);
                }
            });

        $draft->touch();
    }
}
