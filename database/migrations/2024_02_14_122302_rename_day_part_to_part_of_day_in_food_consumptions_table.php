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
        Schema::table('food_consumptions', function (Blueprint $table) {
            // Проверка на существование столбца перед переименованием
            if (Schema::hasColumn('food_consumptions', 'day_part')) {
                $table->renameColumn('day_part', 'part_of_day');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_consumptions', function (Blueprint $table) {
            // Проверка на существование столбца перед переименованием обратно
            if (Schema::hasColumn('food_consumptions', 'part_of_day')) {
                $table->renameColumn('part_of_day', 'day_part');
            }
        });
    }
};
