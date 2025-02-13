<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->longText('description');
            $table->string('recipient');
            $table->string('quote');
            $table->unsignedBigInteger('current_donation')->default(0);
            $table->unsignedBigInteger('target');
            $table->string('banner');
            $table->boolean('accept_donation')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
