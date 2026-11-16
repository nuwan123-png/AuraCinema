<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->string('telegram_url', 500)->nullable()->after('download_1080p');
            $table->timestamp('telegram_added_at')->nullable()->after('telegram_url');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['telegram_url', 'telegram_added_at']);
        });
    }
};