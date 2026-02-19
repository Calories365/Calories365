<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AddGlobalProductCommand extends Command
{
    protected $signature = 'product:add-global
        {name_en : Product name in English}
        {name_ru : Product name in Russian}
        {name_ua : Product name in Ukrainian}
        {calories : Calories per 100 grams}
        {proteins : Proteins per 100 grams}
        {fats : Fats per 100 grams}
        {carbohydrates : Carbohydrates per 100 grams}
        {fibers=0 : Fibers per 100 grams}
        {--popular : Mark product as popular}
        {--skip-duplicate-check : Allow creating product even if a matching global translation already exists}';

    protected $description = 'Create a global product with EN/RU/UA translations and KBJU values';

    public function handle(): int
    {
        $translations = [
            'en' => trim((string) $this->argument('name_en')),
            'ru' => trim((string) $this->argument('name_ru')),
            'ua' => trim((string) $this->argument('name_ua')),
        ];

        $nutrition = [
            'calories' => (float) $this->argument('calories'),
            'proteins' => (float) $this->argument('proteins'),
            'fats' => (float) $this->argument('fats'),
            'carbohydrates' => (float) $this->argument('carbohydrates'),
            'fibers' => (float) $this->argument('fibers'),
        ];

        $validator = Validator::make([
            'name_en' => $translations['en'],
            'name_ru' => $translations['ru'],
            'name_ua' => $translations['ua'],
            ...$nutrition,
        ], [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ru' => ['required', 'string', 'max:255'],
            'name_ua' => ['required', 'string', 'max:255'],
            'calories' => ['required', 'numeric', 'min:0'],
            'proteins' => ['required', 'numeric', 'min:0'],
            'fats' => ['required', 'numeric', 'min:0'],
            'carbohydrates' => ['required', 'numeric', 'min:0'],
            'fibers' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $duplicates = $this->findGlobalTranslationDuplicates($translations);
        if ($duplicates->isNotEmpty() && ! (bool) $this->option('skip-duplicate-check')) {
            $this->warn('Found existing global translations with the same names:');
            $this->table(
                ['translation_id', 'product_id', 'locale', 'name'],
                $duplicates->map(fn (ProductTranslation $t) => [
                    $t->id,
                    $t->product_id,
                    $t->locale,
                    $t->name,
                ])->all()
            );
            $this->line('If you still want to create a new one, run with --skip-duplicate-check.');

            return self::FAILURE;
        }

        $product = DB::transaction(function () use ($translations, $nutrition): Product {
            $product = Product::create([
                'user_id' => null,
                'calories' => $nutrition['calories'],
                'proteins' => $nutrition['proteins'],
                'fats' => $nutrition['fats'],
                'carbohydrates' => $nutrition['carbohydrates'],
                'fibers' => $nutrition['fibers'],
                'is_popular' => (bool) $this->option('popular'),
            ]);

            foreach ($translations as $locale => $name) {
                ProductTranslation::create([
                    'product_id' => $product->id,
                    'locale' => $locale,
                    'name' => $name,
                    'user_id' => null,
                    'active' => 1,
                    'verified' => 1,
                ]);
            }

            return $product;
        });

        $this->info('Global product created successfully.');
        $this->line('product_id: '.$product->id);
        $this->line('EN: '.$translations['en']);
        $this->line('RU: '.$translations['ru']);
        $this->line('UA: '.$translations['ua']);

        return self::SUCCESS;
    }

    private function findGlobalTranslationDuplicates(array $translations): Collection
    {
        return ProductTranslation::query()
            ->select(['id', 'product_id', 'locale', 'name'])
            ->whereNull('user_id')
            ->where(function ($query) use ($translations) {
                foreach ($translations as $locale => $name) {
                    $query->orWhere(function ($subQuery) use ($locale, $name) {
                        $subQuery->where('locale', $locale)
                            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
                    });
                }
            })
            ->orderBy('product_id')
            ->get();
    }
}
