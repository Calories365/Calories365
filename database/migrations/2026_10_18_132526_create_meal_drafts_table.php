<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A meal draft is the meal a user is putting together from voice, text and
     * photos before it goes to the diary; one open draft per user. An item holds
     * the nutrition facts per 100 g it shows: of its product, read from a label,
     * or changed by the user (edited).
     */
    public function up(): void
    {
        Schema::create('meal_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('meal_draft_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_draft_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('name');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->double('calories', 8, 2);
            $table->double('proteins', 8, 2);
            $table->double('fats', 8, 2);
            $table->double('carbohydrates', 8, 2);
            $table->double('fibers', 8, 2)->default(0);
            $table->string('source', 16);
            $table->boolean('edited')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_draft_items');
        Schema::dropIfExists('meal_drafts');
    }
};
