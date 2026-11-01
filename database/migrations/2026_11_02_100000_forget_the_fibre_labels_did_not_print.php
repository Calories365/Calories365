<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A label without fibre was stored as 0 g, and such a product was never asked
     * again: cooked buckwheat, breads and sweets counted no fibre for the day. Zero
     * on a food with carbohydrates becomes unknown; ProductEnricher asks for it when
     * the food is eaten, and sugar or a juice get their 0 back.
     */
    public function up(): void
    {
        DB::table('products')->where('fibers', 0)->where('carbohydrates', '>=', 5)->update(['fibers' => null]);
    }

    public function down(): void
    {
        // What the zeros were is not kept, and unknown fibre is what they meant.
    }
};
