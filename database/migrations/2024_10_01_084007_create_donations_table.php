<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('human_readable_id')->unique();

            $table->string('title');
            $table->string('recipient');
            $table->longText('description');

            $table->string('thumbnail');
            $table->string('program_image');

            $table->unsignedBigInteger('current_donation')->default(0);
            $table->unsignedBigInteger('target');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
