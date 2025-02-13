<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('donations_categories', function (Blueprint $table) {
            $table->uuid('donation_id');
            $table->uuid('category_id');
            $table->foreign('donation_id')->references('id')->on('donations')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['donation_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations_categories');
    }
};
