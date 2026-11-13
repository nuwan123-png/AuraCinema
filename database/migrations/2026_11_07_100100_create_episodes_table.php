<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->integer('number');
            $table->string('url', 500);
            $table->string('subtitle_url', 500)->nullable();
            $table->string('download_url', 500)->nullable(); // අපේ අලුත් Download ලින්ක් එක
            $table->timestamps();

            $table->unique(['movie_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};