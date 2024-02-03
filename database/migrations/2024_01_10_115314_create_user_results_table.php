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
        Schema::create('user_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id'); // ID пользователя
            $table->enum('gender', ['male', 'female']); // Гендер
            $table->year('birth_year'); // Год рождения
            $table->float('weight'); // Вес
            $table->float('height'); // Рост
            $table->float('target_weight'); // Целевой вес
            $table->float('fat_percentage'); // Процент жира
            $table->tinyInteger('activity_level')->unsigned(); // Активность от 1 до 5
            $table->tinyInteger('goal')->unsigned(); // Цель от 1 до 3
            $table->integer('days_required'); // Количество дней
            $table->float('daily_calories'); // Суточная норма калорий
            $table->float('daily_protein'); // Ежедневный белок
            $table->float('daily_carbs'); // Ежедневные углеводы
            $table->float('daily_fats'); // Ежедневные жиры
            $table->timestamps();

            // Внешний ключ для user_id
            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_results');
    }
};
