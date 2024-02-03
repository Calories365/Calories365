<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Отключаем проверку внешних ключей для упрощения массового вставления
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProductTranslation::truncate(); // Очистить таблицу product_translations

        // Извлечение всех продуктов
        $products = Product::all();

        foreach ($products as $product) {
            // Создание перевода на украинском
            ProductTranslation::create([
                'product_id' => $product->id,
                'locale' => 'ua',
                'name' => $product->name_ukr
            ]);

            // Создание перевода на русском
            ProductTranslation::create([
                'product_id' => $product->id,
                'locale' => 'ru',
                'name' => $product->name_rus
            ]);

            // Создание перевода на английском
            ProductTranslation::create([
                'product_id' => $product->id,
                'locale' => 'en',
                'name' => $product->name_eng
            ]);
        }

        // Включаем обратно проверку внешних ключей
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
