<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('movies', 'embed_url')) {
            Schema::table('movies', function (Blueprint $table) {
                $table->text('embed_url')->nullable()->after('trailer_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('movies', 'embed_url')) {
            Schema::table('movies', function (Blueprint $table) {
                $table->dropColumn('embed_url');
            });
        }
    }
};
