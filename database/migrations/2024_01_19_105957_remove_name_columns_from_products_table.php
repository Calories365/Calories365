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
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('name_ukr');
            $table->dropColumn('name_rus');
            $table->dropColumn('name_eng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('name_ukr')->after('id');
            $table->string('name_rus')->after('name_ukr');
            $table->string('name_eng')->after('name_rus');
        });
    }
};
