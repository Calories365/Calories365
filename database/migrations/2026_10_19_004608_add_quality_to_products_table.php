<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * What kind of carbohydrates a food gives and how processed it is: sugars per
     * 100 g, the glycemic index and the NOVA group (1 unprocessed to 4
     * ultra-processed), see ProductQuality. quality_source says where they came
     * from (ai, label); null means nobody asked yet, ProductEnricher fills them.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->double('sugars', 8, 2)->nullable()->after('fibers');
            $table->unsignedTinyInteger('glycemic_index')->nullable()->after('sugars');
            $table->unsignedTinyInteger('nova')->nullable()->after('glycemic_index');
            $table->string('quality_source', 8)->nullable()->after('nova');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sugars', 'glycemic_index', 'nova', 'quality_source']);
        });
    }
};
