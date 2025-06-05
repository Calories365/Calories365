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
            $table->unsignedBigInteger('food_id');

            $table->foreign('food_id')->references('id')->on('products')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_consumptions', function (Blueprint $table) {
            $table->dropForeign(['food_id']);

            $table->dropColumn('food_id');
        });
    }
};
