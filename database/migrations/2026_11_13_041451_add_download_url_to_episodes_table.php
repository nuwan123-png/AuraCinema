<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('download_url')->nullable()->after('embed_url');
        });
    }

    public function down()
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn('download_url');
        });
    }
};