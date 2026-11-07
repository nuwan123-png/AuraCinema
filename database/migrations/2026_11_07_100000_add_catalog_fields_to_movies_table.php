<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // movie | series | anime. Existing rows become "movie" via the default.
            $table->string('type', 10)->default('movie')->index();
            $table->string('genre', 40)->nullable()->index();
            $table->unsignedSmallInteger('year')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['type', 'genre', 'year']);
        });
    }
};
