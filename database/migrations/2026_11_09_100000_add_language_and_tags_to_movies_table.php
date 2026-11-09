<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // Language (Hindi, Tamil...) is its own column so the navbar can filter on an index.
            $table->string('language', 40)->nullable()->index();
            // Free keywords stored as ",hindi,romantic,2026," so an exact tag match is a simple LIKE.
            $table->string('tags', 400)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['language', 'tags']);
        });
    }
};
