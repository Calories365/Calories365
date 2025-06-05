<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_translations', function (Blueprint $table) {
            $table->dropColumn(['soundexed_name', 'double_metaphoned_name', 'transliterated_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_translations', function (Blueprint $table) {
            $table->string('soundexed_name')->nullable();
            $table->string('double_metaphoned_name')->nullable();
            $table->string('transliterated_name')->nullable();
        });
    }
};
