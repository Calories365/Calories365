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
        Schema::table('food_consumptions', function (Blueprint $table) {
            // Добавление столбца consumed_at с типом datetime
            $table->dateTime('consumed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('food_consumptions', function (Blueprint $table) {
            $table->dropColumn('consumed_at');
        });
    }
};
