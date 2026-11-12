<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // gallery: ["gallery/name-2026-ab12cd.webp", ...]   cast_members: [{"name","role","photo"}, ...]
            $table->json('gallery')->nullable();
            $table->json('cast_members')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['gallery', 'cast_members']);
        });
    }
};
