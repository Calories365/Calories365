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
        Schema::create('user_added_foods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('ukr_name')->nullable();
            $table->string('food_group')->nullable();
            $table->decimal('calories', 8, 2);
            $table->decimal('fat', 8, 2);
            $table->decimal('protein', 8, 2);
            $table->decimal('carbohydrate', 8, 2);
            $table->decimal('sugars', 8, 2)->nullable();
            $table->integer('serving_size')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_added_foods');
    }
};
