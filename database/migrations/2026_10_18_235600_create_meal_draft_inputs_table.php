<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * What a user sends about a meal, voice, text, photos of dishes and labels,
     * waits here until the user asks to calculate: then all of it goes to the
     * model in one request and becomes changes of the meal draft. A photo is kept
     * on the local disk until then.
     */
    public function up(): void
    {
        Schema::create('meal_draft_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 8);
            $table->text('text')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_mime', 32)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_draft_inputs');
    }
};
