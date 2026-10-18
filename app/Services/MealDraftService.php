<?php

namespace App\Services;

use App\Jobs\CalculateMealDraftJob;
use App\Models\FoodConsumption;
use App\Models\MealDraft;
use App\Models\MealDraftInput;
use App\Models\MealDraftItem;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The meal draft: a meal put together from several messages, voice, text,
 * photos of the dish and of labels, in any order, and saved to the diary at once.
 *
 * What the user sends waits as inputs (collect) until the user asks to
 * calculate: a queued job (CalculateMealDraftJob) gives all of them to the model
 * in one request with the draft, so a label, a photo of the dish and the words
 * about the same food make one item. The model's answer is operations on the
 * draft (MealInputReader): add a food, correct an item, remove one. A short
 * edit, "2 250" or "убрать 3", needs no model and applies at once. The foods
 * named get their products from ProductCatalog, all of one calculation in one
 * call; a brand gets a product of its own and a label makes its own product. The
 * draft keeps the facts each item shows; nutrition facts the user tells mark the
 * item edited, and it is saved as the user's own product.
 */
class MealDraftService
{
    /**
     * "2 250": grams of item 2.
     */
    private const SET_GRAMS = '/^(\d{1,2})\s*[-—:.]?\s*(\d{1,4})\s*(?:г|гр|грам\p{L}*|g)?\.?$/u';

    /**
     * "убрать 3": remove item 3.
     */
    private const REMOVE = '/^(?:-|убра\p{L}*|убери|удал\p{L}*|прибра\p{L}*|прибери|видал\p{L}*|remove|delete)\s*(\d{1,2})$/u';

    /**
     * The state of the user's calculation, see calculation().
     */
    private const CALCULATION_KEY = 'meal_draft_calculation_';

    /**
     * Seconds after which a calculation that never finished counts as failed:
     * longer than the job may run.
     */
    private const CALCULATION_LIMIT = 200;

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
        return $this->apply($userId, $locale, [[
            'kind' => $photo === null ? MealDraftInput::KIND_TEXT : MealDraftInput::KIND_PHOTO,
            'text' => $text,
            'photo' => $photo,
        ]]);
    }

    /**
     * Keep an input until the calculation: a voice message (its words), a text
     * or a photo with its caption. A short edit of the draft applies at once
     * instead, and then is the result of input().
     *
     * @return array{draft: MealDraft|null, understood: bool, not_found: array<int, string>}|null
     *
     * @throws \LengthException when the user has as many inputs waiting as a calculation takes
     */
    public function collect(int $userId, string $locale, string $kind, ?string $text, ?UploadedFile $photo = null): ?array
    {
        if ($photo === null && self::isShortEdit((string) $text) && MealDraft::openFor($userId) !== null) {
            return $this->input($userId, $locale, $text, null);
        }

        $pending = MealDraftInput::pendingFor($userId);

        if ($pending->count() >= MealDraftInput::MAX_PENDING
            || ($photo !== null && $pending->whereNotNull('photo_path')->count() >= MealDraftInput::MAX_PHOTOS)) {
            throw new \LengthException('Too many inputs wait for the calculation');
        }

        MealDraftInput::create([
            'user_id' => $userId,
            'kind' => $kind,
            'text' => $text !== null && trim($text) !== '' ? trim($text) : null,
            'photo_path' => $photo?->store('meal-inputs/'.$userId, MealDraftInput::DISK),
            'photo_mime' => $photo ? (string) $photo->getMimeType() : null,
        ]);

        // What a finished calculation reported is about the inputs it took.
        if (($this->calculation($userId)['status'] ?? null) !== 'running') {
            Cache::forget(self::CALCULATION_KEY.$userId);
        }

        return null;
    }

    /**
     * Start the calculation of the inputs waiting: a queued job, since the model
     * reads several photos for half a minute and more. Inputs sent meanwhile wait
     * for the next calculation.
     *
     * @return array<string, mixed>|null the state of the calculation, null when nothing waits
     */
    public function calculate(int $userId, string $locale): ?array
    {
        $state = $this->calculation($userId);
        if (($state['status'] ?? null) === 'running') {
            return $state;
        }

        $inputs = MealDraftInput::pendingFor($userId);
        if ($inputs->isEmpty()) {
            return null;
        }

        $state = ['status' => 'running', 'started_at' => now()->timestamp];
        Cache::put(self::CALCULATION_KEY.$userId, $state, now()->addMinutes(10));

        CalculateMealDraftJob::dispatch($userId, $locale, $inputs->pluck('id')->all());

        // A synchronous queue has finished it by now.
        return $this->calculation($userId);
    }

    /**
     * Apply the inputs to the draft in one model call and drop them; the
     * outcome becomes the state of the calculation.
     *
     * @param  array<int, int>  $inputIds
     */
    public function runCalculation(int $userId, string $locale, array $inputIds): void
    {
        $inputs = MealDraftInput::query()->where('user_id', $userId)->whereIn('id', $inputIds)->orderBy('id')->get();

        try {
            $result = $inputs->isEmpty()
                ? ['understood' => false, 'not_found' => []]
                : $this->apply($userId, $locale, $inputs->map(static fn (MealDraftInput $input) => [
                    'kind' => $input->kind,
                    'text' => $input->text,
                    'photo' => $input->photo(),
                ])->all());
        } catch (\Throwable $e) {
            Log::warning('Meal draft calculation failed', ['error' => $e->getMessage()]);
            Cache::put(self::CALCULATION_KEY.$userId, ['status' => 'failed'], now()->addMinutes(10));

            return;
        }

        // The inputs are taken whatever the model made of them: sending the same
        // again would give the same answer.
        $inputs->each->delete();

        Cache::put(self::CALCULATION_KEY.$userId, [
            'status' => 'done',
            'understood' => $result['understood'],
            'not_found' => $result['not_found'],
        ], now()->addMinutes(10));
    }

    /**
     * The state of the user's last calculation: running, done with what it
     * understood and the foods it found no product for, or failed; null without one.
     *
     * @return array<string, mixed>|null
     */
    public function calculation(int $userId): ?array
    {
        $state = Cache::get(self::CALCULATION_KEY.$userId);

        // The job died without a word: it was killed or the queue lost it.
        if (($state['status'] ?? null) === 'running' && now()->timestamp - ($state['started_at'] ?? 0) > self::CALCULATION_LIMIT) {
            return ['status' => 'failed'];
        }

        return $state;
    }

    /**
     * Drop one input waiting for the calculation.
     */
    public function removeInput(int $userId, int $inputId): bool
    {
        $input = MealDraftInput::query()->where('user_id', $userId)->find($inputId);

        return (bool) $input?->delete();
    }

    /**
     * Drop all the inputs waiting for the calculation.
     */
    public function clearInputs(int $userId): void
    {
        MealDraftInput::query()->where('user_id', $userId)->get()->each->delete();
        Cache::forget(self::CALCULATION_KEY.$userId);
    }

    /**
     * Apply inputs to the user's draft in one model call; a single short edit
     * needs none.
     *
     * @param  array<int, array{kind: string, text: string|null, photo: array{mime: string, contents: string}|null}>  $inputs
     * @return array{draft: MealDraft|null, understood: bool, not_found: array<int, string>}
     *
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \RuntimeException
     */
    private function apply(int $userId, string $locale, array $inputs): array
    {
        $draft = MealDraft::openFor($userId);
        $items = $draft ? $draft->items->values() : collect();

        $read = (count($inputs) === 1 && $inputs[0]['photo'] === null ? $this->shortEdit((string) $inputs[0]['text'], $items) : null)
            ?? $this->reader->read($items->map(static fn (MealDraftItem $item) => ['name' => $item->name, 'grams' => $item->quantity])->all(), $inputs, $locale);

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
        $this->clearInputs($userId);
    }

    /**
     * Change an item from the draft editor: grams are the portion; a name or
     * nutrition facts the user types make the item the user's own (edited).
     *
     * @param  array{grams?: int|float, name?: string, calories?: float, proteins?: float, fats?: float, carbohydrates?: float}  $changes
     * @return MealDraft|null the draft, or null when the user has no such item
     */
    public function updateItem(int $userId, int $itemId, array $changes): ?MealDraft
    {
        $draft = MealDraft::openFor($userId);
        $item = $draft?->items->firstWhere('id', $itemId);

        if ($item === null) {
            return null;
        }

        if (isset($changes['grams'])) {
            $item->quantity = (int) round($changes['grams']);
        }

        $own = array_intersect_key($changes, array_flip(['name', 'calories', 'proteins', 'fats', 'carbohydrates']));
        if ($own !== []) {
            $item->fill($own + ['edited' => true]);
        }

        $item->save();
        $draft->touch();

        return $draft->fresh();
    }

    /**
     * Remove an item from the draft editor; the draft goes with its last item.
     *
     * @return MealDraft|false|null the draft, null when it is gone, false when the user has no such item
     */
    public function removeItem(int $userId, int $itemId): MealDraft|false|null
    {
        $draft = MealDraft::openFor($userId);
        $item = $draft?->items->firstWhere('id', $itemId);

        if ($item === null) {
            return false;
        }

        $item->delete();

        if ($draft->items()->doesntExist()) {
            $draft->delete();

            return null;
        }

        $this->renumber($draft);

        return $draft->fresh();
    }

    /**
     * The words of a voice recording from the site or the Mini App; null when
     * it has none. The file is removed once read.
     *
     * @throws \RuntimeException when the transcription fails
     */
    public function transcribe(UploadedFile $audio, AudioConversionService $converter, SpeechToTextService $speech): ?string
    {
        $extension = strtolower($audio->getClientOriginalExtension() ?: 'webm');
        $localPath = $audio->storeAs('voice_records', Str::uuid().'.'.$extension, 'public');
        $fullPath = Storage::disk('public')->path($localPath);

        // gpt-transcribe takes the webm, mp4 and mp3 browsers record; only ogg needs converting.
        [$mp3Local, $mp3Full] = in_array($extension, ['ogg', 'oga'], true)
            ? $converter->convertToMp3($localPath, $fullPath)
            : [null, null];

        try {
            $text = $speech->transcribe($mp3Full ?: $fullPath);
        } finally {
            Storage::disk('public')->delete(array_filter([$localPath, $mp3Local]));
        }

        return $text === false ? null : $text;
    }

    /**
     * Whether the text is a short edit the draft applies without the model.
     */
    public static function isShortEdit(string $text): bool
    {
        $text = mb_strtolower(trim($text));

        return (bool) preg_match(self::SET_GRAMS, $text) || (bool) preg_match(self::REMOVE, $text);
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

        if (preg_match(self::SET_GRAMS, $text, $match)
            && isset($items[$match[1] - 1]) && $match[2] > 0) {
            return ['operations' => [['action' => 'update', 'item' => (int) $match[1], 'name' => '', 'grams' => (float) $match[2], 'label' => null, 'facts' => []]], 'labels' => []];
        }

        if (preg_match(self::REMOVE, $text, $match)
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
        $branded = [];

        foreach ($read['operations'] as $index => $operation) {
            if ($operation['action'] === 'remove') {
                continue;
            }

            if ($operation['label'] !== null) {
                $hits[$index] = ['hit' => $this->catalog->addFromLabel($read['labels'][$operation['label']], $locale, $userId), 'source' => MealDraftItem::SOURCE_LABEL];
            } elseif ($operation['name'] !== '' && ! $this->toldAllFacts($operation)) {
                if (($operation['brand'] ?? '') !== '') {
                    $branded[$index] = ['name' => $operation['name'], 'brand' => $operation['brand']];
                } else {
                    $names[$index] = $operation['name'];
                }
            }
        }

        if ($branded !== []) {
            foreach ($this->catalog->findBranded($branded, $userId, $locale) as $index => $hit) {
                $hits[$index] = $hit ? ['hit' => $hit, 'source' => MealDraftItem::SOURCE_DATABASE] : null;
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
