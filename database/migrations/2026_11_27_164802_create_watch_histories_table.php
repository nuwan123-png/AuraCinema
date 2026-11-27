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
    Schema::create('watch_histories', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->unsignedBigInteger('movie_id');
        $table->unsignedBigInteger('episode_id')->default(0); // 0 = Movie, >0 = Episode ID
        $table->string('title');
        $table->string('poster_url')->nullable();
        $table->string('type')->default('movie'); // 'movie' or 'series'
        $table->unsignedInteger('position_seconds')->default(0);
        $table->unsignedInteger('duration_seconds')->default(0);
        $table->timestamps();

        $table->unique(['user_id', 'movie_id', 'episode_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_histories');
    }
};
