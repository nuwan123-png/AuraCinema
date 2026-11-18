<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->date('viewed_on');
            // Anonymous per-day fingerprint (keyed hash of IP + browser + date). No raw IP address is stored.
            $table->string('visitor', 32);
            $table->string('path', 255);
            $table->unsignedBigInteger('movie_id')->nullable();
            $table->string('referrer_host', 100)->nullable();
            $table->string('device', 10)->default('desktop');
            $table->timestamp('created_at')->nullable();

            $table->index(['viewed_on', 'visitor']);
            $table->index(['movie_id', 'viewed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
