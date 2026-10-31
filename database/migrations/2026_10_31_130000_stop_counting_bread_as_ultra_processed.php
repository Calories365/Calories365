<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Products of a label keep what they got under the old rule, since a label is not
 * asked about again: its printed sugars would be lost. Its NOVA group was the
 * model's estimate all the same, and bread from a package came as group 4. Bread
 * is group 3 now, unless it is named toast or hamburger bread.
 */
return new class extends Migration
{
    private const BREAD = ['хлеб', 'хліб', 'батон', 'булк', 'булоч', 'лаваш', 'багет', 'чиабат', 'bread', 'baguette', 'ciabatta', 'lavash', 'loaf'];

    private const NOT_THIS_BREAD = ['батонч', 'хлебц', 'хлібц', 'хлебн', 'хлібн', 'тост', 'гамбург', 'бургер', 'toast', 'burger', 'crispbread', 'shortbread', 'breadcrumb', 'breaded'];

    public function up(): void
    {
        DB::table('products')
            ->where('nova', 4)
            ->whereIn('id', $this->productsNamed(self::BREAD))
            ->whereNotIn('id', $this->productsNamed(self::NOT_THIS_BREAD))
            ->update(['nova' => 3]);
    }

    public function down(): void
    {
        // The old estimates are not kept: they were wrong.
    }

    /**
     * @param  array<int, string>  $words
     */
    private function productsNamed(array $words): \Illuminate\Database\Query\Builder
    {
        return DB::table('product_translations')
            ->select('product_id')
            ->where(static function ($query) use ($words) {
                foreach ($words as $word) {
                    $query->orWhere('name', 'like', "%{$word}%");
                }
            });
    }
};
