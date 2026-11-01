<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An activity remembers which kind of the catalogue it is (ActivityCatalog):
     * a walk, a run, a night's sleep; older ones were told only by their MET.
     *
     * A product gets its food insulin index (ProductQuality): the insulin of the
     * curve was a regression on its nutrients, which misses milk products by half.
     * Products the model answered about are asked again, which also gives the
     * missing fibre to the thousands of products without it (ProductEnricher).
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('type', 30)->nullable()->after('started_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedSmallInteger('insulin_index')->nullable()->after('glycemic_index');
        });

        DB::table('products')->where('quality_source', 'ai')->update(['quality_source' => null]);
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('insulin_index'));
        Schema::table('activities', fn (Blueprint $table) => $table->dropColumn('type'));
    }
};
