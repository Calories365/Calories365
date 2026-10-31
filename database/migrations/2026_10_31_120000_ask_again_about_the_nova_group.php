<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The model was told that packaged bread is ultra-processed and took plain
 * "Хлеб" or "Батон" for packaged; plain "Творог" came as group 1 or 3 from one
 * answer to the next. The rule is corrected in ProductQuality, and products the
 * model answered about under the old one are asked again (ProductEnricher) when
 * they are next eaten or by products:enrich. What a label gave is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->where('quality_source', 'ai')
            ->update(['nova' => null, 'quality_source' => null]);
    }

    public function down(): void
    {
        // The old answers are not kept: they were wrong.
    }
};
