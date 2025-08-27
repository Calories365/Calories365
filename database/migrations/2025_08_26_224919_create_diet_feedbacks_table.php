<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diet_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->date('feedback_date')->index();
            $table->string('part_of_day', 16)->nullable()->index(); // morning|dinner|supper|null(весь день)
            $table->string('meals_signature', 64)->index();         // sha256 по id:grams
            $table->text('feedback_text')->nullable();
            $table->string('status', 16)->default('pending');       // pending|ready|failed
            $table->timestamps();

            $table->unique(['user_id', 'feedback_date', 'part_of_day', 'meals_signature'], 'diet_feedbacks_unique_cache');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_feedbacks');
    }
};
