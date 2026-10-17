<?php

use App\Models\ProductTranslation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ProductCatalog adds the foods the database lacks, with nutrition facts the
     * model estimated; source = 'ai' tells them from the products people entered,
     * and the search prefers the latter when both fit.
     *
     * The products index gets the stems and the cooking method of every name,
     * which ProductMatcher searches and filters by, so the index is imported anew.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('source', 16)->nullable()->after('user_id');
        });

        ProductTranslation::makeAllSearchable();
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
