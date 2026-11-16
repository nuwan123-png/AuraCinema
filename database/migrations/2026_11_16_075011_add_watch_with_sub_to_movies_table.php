<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('movies', function (Blueprint $table) {
        $table->boolean('watch_with_sub')->default(false)->after('status');
    });
}

public function down(): void
{
    Schema::table('movies', function (Blueprint $table) {
        $table->dropColumn('watch_with_sub');
    });
}
};
