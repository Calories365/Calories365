<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_added_food_consumptions', function (Blueprint $table) {
            $table->id('consumption_id');
            $table->unsignedBigInteger('user_added_food_id');
            $table->timestamps();

            $table->foreign('consumption_id')->references('id')->on('food_consumptions')
                ->onDelete('cascade');
            $table->foreign('user_added_food_id')->references('id')->on('user_added_foods')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_added_food_consumptions');
    }
};
