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
        Schema::table('user_results', function (Blueprint $table) {
            $table->boolean('checkboxActive')->default(false);
        });
    }

    public function down()
    {
        Schema::table('user_results', function (Blueprint $table) {
            $table->dropColumn('checkboxActive');
        });
    }
};
