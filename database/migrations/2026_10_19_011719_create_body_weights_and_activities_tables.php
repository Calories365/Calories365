<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The body weight a user tells, one per day of the user (the last one
     * counts), and the physical activities of a day with the calories they
     * burn above rest: the weight trend against what the diary says was eaten
     * gives the user's real daily expenditure (EnergyBalance).
     */
    public function up(): void
    {
        Schema::create('body_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->double('weight', 5, 2);
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('title', 150);
            $table->unsignedSmallInteger('minutes');
            $table->double('met', 4, 1);
            $table->unsignedSmallInteger('calories');
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('body_weights');
    }
};
