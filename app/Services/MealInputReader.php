<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Reads what a user said, typed or photographed about a meal into changes of
 * the meal draft, in one request to the meal model.
 *
 * The model sees the draft, so "гречки было 250" or "убери хлеб" change the item
 * the user means. It gets everything the user sent for the calculation at once,
 * voice messages, texts and photos in the order they came, and puts them
 * together: a label and a photo of the dish of the same food make one item. A
 * photo of a dish gives its foods with an estimate of the portions; a photo of a
 * label gives the product's nutrition facts exactly as printed. The model names
 * foods and their brands; their nutrition facts come from the database through
 * ProductCatalog, or from the label.
 */
class MealInputReader
{
    /**
     * Grams of one item beyond which the amount is taken for a slip.
     */
    private const MAX_GRAMS = 5000;

    public function __construct(private ProductMatcher $matcher) {}

    /**
     * The operations on the draft and the labels read; a label whose facts cannot
     * be true is null, and an operation pointing at no item is dropped.
     *
     * @param  array<int, array{name: string, grams: int}>  $draft  the items of the draft, numbered from 1 by position
     * @param  array<int, array{kind: string, text: string|null, photo: array{mime: string, contents: string}|null}>  $inputs  in the order the user sent them
     * @return array{operations: array<int, array{action: string, item: int|null, name: string, brand: string, grams: float|null, label: int|null, facts: array<string, float>}>, labels: array<int, array<string, mixed>|null>}
     *
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \RuntimeException
     */
    public function read(array $draft, array $inputs, string $locale): array
    {
        $content = [['type' => 'text', 'text' => $this->prompt($draft, $inputs, $locale)]];

        foreach ($inputs as $input) {
            if ($input['photo'] !== null) {
                $content[] = [
                    'type' => 'image_url',
                    'image_url' => ['url' => 'data:'.$input['photo']['mime'].';base64,'.base64_encode($input['photo']['contents'])],
                ];
            }
        }

        $keys = config('services.openai.keys');

        // Several photos take the model half a minute and more.
        $result = Http::timeout(150)
            ->withToken((string) ($keys[$locale] ?? $keys['ru'] ?? ''))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.models.meal_analysis'),
                'reasoning_effort' => config('services.openai.reasoning_effort'),
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => ['name' => 'meal_draft', 'strict' => true, 'schema' => $this->schema()],
                ],
                'messages' => [['role' => 'user', 'content' => $content]],
            ])
            ->throw()
            ->json();

        $decoded = json_decode((string) ($result['choices'][0]['message']['content'] ?? ''), true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('The meal model gave no JSON');
        }

        $labels = array_map(fn ($label) => is_array($label) ? $this->validLabel($label) : null, $decoded['labels'] ?? []);

        $operations = [];
        foreach ($decoded['operations'] ?? [] as $operation) {
            $operation = $this->validOperation($operation, count($draft), $labels);
            if ($operation !== null) {
                $operations[] = $operation;
            }
        }

        return ['operations' => $operations, 'labels' => $labels];
    }

    /**
     * @param  array<int, array{name: string, grams: int}>  $draft
     * @param  array<int, array{kind: string, text: string|null, photo: array{mime: string, contents: string}|null}>  $inputs
     */
    private function prompt(array $draft, array $inputs, string $locale): string
    {
        $language = match ($locale) {
            'ua' => 'Ukrainian',
            'en' => 'English',
            default => 'Russian',
        };

        $lines = [];
        foreach ($draft as $index => $item) {
            $lines[] = ($index + 1).'. '.$item['name'].' — '.$item['grams'].' g';
        }

        $sent = [];
        $photos = 0;
        foreach ($inputs as $input) {
            $text = trim((string) $input['text']);

            if ($input['photo'] !== null) {
                $photos++;
                $sent[] = "Photo {$photos} (attached image {$photos})".($text === '' ? '' : ', caption: '.$text);
            } elseif ($text !== '') {
                $sent[] = ($input['kind'] === 'voice' ? 'Voice message: ' : 'Text: ').$text;
            }
        }

        foreach ($sent as $index => $line) {
            $sent[$index] = ($index + 1).'. '.$line;
        }

        return "You keep the draft of a meal in a calorie diary. The user speaks {$language}.\n\n"
            ."The draft now:\n".($lines === [] ? '(empty)' : implode("\n", $lines))."\n\n"
            ."New input from the user, in the order it was sent; the photos are attached in the same order:\n"
            .($sent === [] ? '(none)' : implode("\n", $sent))."\n\n"
            .'All of the input is about this one meal: put it together. Words, a photo of the dish and a photo of '
            .'a label may show the same food: it is one item, with the facts of the label, the amount the words '
            .'give, otherwise the amount the dish shows. What the user says, an amount, a fat content, a brand, '
            .'is kept as said; a photo adds what the words do not tell, and only printed text on it overrides '
            ."the words. A later message may correct an earlier one; the later one wins.\n\n"
            ."Return the operations that turn the draft into what the user ate:\n"
            ."- add: a food the user ate that the draft does not have yet.\n"
            .'- update: the user corrects a draft item: "item" is its number; give the new grams, the new name, '
            ."or both. \"name\" is \"\" when the name stays, \"grams\" is null when the amount stays.\n"
            ."- remove: the user says a draft item was not eaten; \"item\" is its number.\n\n"
            ."Names, for add and for a changed name: in {$language}, the way a nutrition database names the food: "
            .'the food first, then what describes it, singular, nominative. Keep the cooking method, the sort, the '
            .'fat content, the brand and what was added as the user said or the photo shows them. When the method is '
            .'not said and the food is eaten only cooked (grains, pasta, legumes, potatoes, meat, fish), name its '
            .'usual method, "Гречка варёная". No parentheses in names. A soup with sour cream or a salad with its '
            ."dressing is one item.\n\n"
            .'Grams: the amount eaten. Take it from the text; for pieces multiply by the weight of one piece; for '
            .'drinks 1 ml is 1 g. Without an amount in the text, estimate it from the photo by the plate, the cutlery '
            ."and the usual portion, or take a usual portion. Never leave grams null for add.\n\n"
            .'A photo of a dish: add every food on it with its estimated grams. A photo of a nutrition label: read '
            .'each product whose table of nutrition facts is printed and legible on the photo into "labels" '
            .'("facts_visible" true); a package without a legible table is a food like any other, added by its name, '
            .'and never gets facts made up. A label holds the name in Russian (name_ru), Ukrainian (name_ua) and '
            .'English (name_en), product type first, then brand and fat content; "named" is false when neither the '
            .'package nor the text tells what the product is, and the names then say only its kind; calories, '
            .'proteins, fats, carbohydrates and fibers per 100 g (per 100 ml counts as per 100 g; values only per '
            .'serving are converted to 100 g by the serving weight; fibers 0 when not shown); package_grams, the net '
            .'weight, or null. Then add each product the user ate, or update the draft item it belongs to, with '
            .'"label" set to its index in "labels"; otherwise "label" is null. A product on a label that the text '
            ."says the user did not eat is not added.\n\n"
            .'Nutrition facts the user tells for a food ("во втором 250 калорий на 100 грамм", "белка 20"): '
            .'calories, proteins, fats, carbohydrates per 100 g of an add or update; facts per portion are '
            ."converted to 100 g by its grams. A fact the user did not tell is null.\n\n"
            .'Brand: for add and for a changed name, the brand or the maker of the product when the user names one '
            .'or a package or a label on a photo shows it, spelled the same as in the name, which keeps it; "" for a food '
            ."without a brand: a dish, a fruit, a home-made food, a product of no visible make.\n\n"
            .'When the input says nothing about food, return no operations and no labels.';
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        $number = ['type' => 'number'];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['operations', 'labels'],
            'properties' => [
                'operations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['action', 'item', 'name', 'brand', 'grams', 'label', 'calories', 'proteins', 'fats', 'carbohydrates'],
                        'properties' => [
                            'action' => ['type' => 'string', 'enum' => ['add', 'update', 'remove']],
                            'item' => ['type' => ['integer', 'null']],
                            'name' => ['type' => 'string'],
                            'brand' => ['type' => 'string'],
                            'grams' => ['type' => ['number', 'null']],
                            'label' => ['type' => ['integer', 'null']],
                            'calories' => ['type' => ['number', 'null']],
                            'proteins' => ['type' => ['number', 'null']],
                            'fats' => ['type' => ['number', 'null']],
                            'carbohydrates' => ['type' => ['number', 'null']],
                        ],
                    ],
                ],
                'labels' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['facts_visible', 'name_ru', 'name_ua', 'name_en', 'named', 'calories', 'proteins', 'fats', 'carbohydrates', 'fibers', 'package_grams'],
                        'properties' => [
                            'facts_visible' => ['type' => 'boolean'],
                            'name_ru' => ['type' => 'string'],
                            'name_ua' => ['type' => 'string'],
                            'name_en' => ['type' => 'string'],
                            'named' => ['type' => 'boolean'],
                            'calories' => $number,
                            'proteins' => $number,
                            'fats' => $number,
                            'carbohydrates' => $number,
                            'fibers' => $number,
                            'package_grams' => ['type' => ['number', 'null']],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * The operation, or null when it points at no item or adds nothing; facts
     * are the nutrition facts per 100 g the user told.
     *
     * @param  mixed  $operation
     * @param  array<int, array<string, mixed>|null>  $labels
     * @return array{action: string, item: int|null, name: string, brand: string, grams: float|null, label: int|null, facts: array<string, float>}|null
     */
    private function validOperation($operation, int $items, array $labels): ?array
    {
        if (! is_array($operation)) {
            return null;
        }

        $action = $operation['action'] ?? null;
        $item = is_int($operation['item'] ?? null) ? $operation['item'] : null;
        $name = trim((string) ($operation['name'] ?? ''));
        $brand = $name === '' ? '' : mb_substr(trim((string) ($operation['brand'] ?? '')), 0, 60);

        // The product is found and stored by its name, so the brand must be in it.
        if ($brand !== '' && mb_stripos($name, $brand) === false) {
            $name .= ' '.$brand;
        }
        $grams = is_numeric($operation['grams'] ?? null) ? (float) $operation['grams'] : null;
        $label = is_int($operation['label'] ?? null) && ($labels[$operation['label']] ?? null) !== null ? $operation['label'] : null;

        if ($grams !== null && ($grams <= 0 || $grams > self::MAX_GRAMS)) {
            $grams = null;
        }

        $facts = [];
        foreach (['calories', 'proteins', 'fats', 'carbohydrates'] as $field) {
            $value = $operation[$field] ?? null;
            if (is_numeric($value) && $value >= 0 && $value <= ($field === 'calories' ? 950 : 100)) {
                $facts[$field] = round((float) $value, 2);
            }
        }

        $fits = match ($action) {
            'add' => ($name !== '' || $label !== null) && $grams !== null,
            'update' => $item !== null && $item >= 1 && $item <= $items && ($name !== '' || $grams !== null || $label !== null || $facts !== []),
            'remove' => $item !== null && $item >= 1 && $item <= $items,
            default => false,
        };

        return $fits ? ['action' => $action, 'item' => $item, 'name' => mb_substr($name, 0, 150), 'brand' => $brand, 'grams' => $grams, 'label' => $label, 'facts' => $facts] : null;
    }

    /**
     * The label as stored facts, or null when the photo does not show its facts
     * or they cannot be true.
     *
     * @param  array<string, mixed>  $label
     * @return array{names: array<string, string>, named: bool, calories: float, proteins: float, fats: float, carbohydrates: float, fibers: float}|null
     */
    private function validLabel(array $label): ?array
    {
        // Facts the photo does not show are made up, whatever the package says.
        if (($label['facts_visible'] ?? false) !== true) {
            return null;
        }

        $names = [];
        foreach (['ru' => 'name_ru', 'ua' => 'name_ua', 'en' => 'name_en'] as $locale => $field) {
            $name = trim((string) ($label[$field] ?? ''));
            if ($name === '' || mb_strlen($name) > 150) {
                return null;
            }
            $names[$locale] = $name;
        }

        $values = [];
        foreach (['calories', 'proteins', 'fats', 'carbohydrates', 'fibers'] as $field) {
            $value = $label[$field] ?? null;
            if (! is_numeric($value) || $value < 0 || $value > ($field === 'calories' ? 950 : 100)) {
                return null;
            }
            $values[$field] = round((float) $value, 2);
        }

        if ($values['proteins'] + $values['fats'] + $values['carbohydrates'] > 105
            || ! $this->matcher->plausible(FoodName::parse($names['ru']), $values)) {
            return null;
        }

        return ['names' => $names, 'named' => ($label['named'] ?? false) === true] + $values;
    }
}
