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
            $table->unsignedSmallInteger('number');
            $table->string('url', 500);
            $table->timestamps();

            // One link per episode number; also makes "episodes of this series, in order" an index lookup.
            $table->unique(['movie_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
