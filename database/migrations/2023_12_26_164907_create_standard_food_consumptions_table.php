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
        Schema::create('standard_food_consumptions', function (Blueprint $table) {
            $table->id('consumption_id');
            $table->unsignedBigInteger('food_id');
            $table->timestamps();

            $table->foreign('consumption_id')->references('id')->on('food_consumptions')
                ->onDelete('cascade');
            $table->foreign('food_id')->references('id')->on('foods')
                ->onDelete('restrict');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_food_consumptions');
    }
};
