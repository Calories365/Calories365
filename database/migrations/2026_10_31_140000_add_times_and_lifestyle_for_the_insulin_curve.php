<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The insulin curve of a day needs to know when things happened: a meal or an
     * activity without a time told counts by when it was saved (InsulinDay). The way
     * of life is one of the calculator's five levels, kept apart from its form, so
     * that changing it here does not change the calorie norm.
     */
    public function up(): void
    {
        Schema::table('food_consumptions', function (Blueprint $table) {
            $table->dateTime('eaten_at')->nullable()->after('consumed_at');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dateTime('started_at')->nullable()->after('date');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('lifestyle')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('lifestyle'));
        Schema::table('activities', fn (Blueprint $table) => $table->dropColumn('started_at'));
        Schema::table('food_consumptions', fn (Blueprint $table) => $table->dropColumn('eaten_at'));
    }
};
