<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('food_consumptions', function (Blueprint $table) {
            $table->string('part_of_day')->after('quantity')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_consumptions', function (Blueprint $table) {
            // Удаляем поле 'day_part', если оно существует
            $table->dropColumn('day_part');
        });
    }
};
