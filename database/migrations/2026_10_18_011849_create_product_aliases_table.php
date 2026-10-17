<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A food ProductCatalog added stays tied to the name the user said, read by
     * FoodName ("baked:кур ножк"): a name the product's own name does not match,
     * "Курица221" or a dish of an unusual cooking method, is found again without
     * asking the model a second time.
     */
    public function up(): void
    {
        Schema::create('product_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 8);
            $table->string('name', 191);
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['locale', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_aliases');
    }
};
