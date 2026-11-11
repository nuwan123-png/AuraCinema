<?php

namespace Tests\Feature;

use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CopyLegacyDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private function fakeLegacy(): void
    {
        config(['database.connections.legacy' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('legacy');

        Schema::connection('legacy')->create('movies', function ($t) {
            $t->id();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('tags')->nullable();
            $t->timestamps();
        });
        Schema::connection('legacy')->create('episodes', function ($t) {
            $t->id();
            $t->unsignedBigInteger('movie_id');
            $t->unsignedSmallInteger('number');
            $t->string('url', 500);
            $t->timestamps();
        });
    }

    public function test_copies_rows_keeps_ids_and_new_rows_get_fresh_ids(): void
    {
        $this->fakeLegacy();
        DB::connection('legacy')->table('movies')->insert([
            ['id' => 7, 'title' => 'Island Python', 'description' => 'd', 'tags' => ',monster,', 'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-01 10:00:00'],
            ['id' => 9, 'title' => 'Cocktail 2', 'description' => null, 'tags' => null, 'created_at' => '2026-01-02 10:00:00', 'updated_at' => '2026-01-02 10:00:00'],
        ]);
        DB::connection('legacy')->table('episodes')->insert([
            ['id' => 3, 'movie_id' => 7, 'number' => 1, 'url' => 'https://example.com/ep1', 'created_at' => null, 'updated_at' => null],
        ]);

        $this->artisan('db:copy-legacy')->assertSuccessful();

        $this->assertSame(2, Movie::count());
        $this->assertSame('Island Python', Movie::find(7)->title);
        $this->assertSame(1, DB::table('episodes')->where('movie_id', 7)->count());

        // The id counter must continue after the copied rows (this broke on Postgres without setval).
        $new = Movie::create(['title' => 'Brand new']);
        $this->assertGreaterThan(9, $new->id);
    }

    public function test_refuses_to_copy_twice(): void
    {
        $this->fakeLegacy();
        DB::connection('legacy')->table('movies')->insert(['id' => 1, 'title' => 'A', 'created_at' => null, 'updated_at' => null]);

        $this->artisan('db:copy-legacy')->assertSuccessful();
        $this->artisan('db:copy-legacy')->assertFailed();
        $this->assertSame(1, Movie::count());
    }

    public function test_search_ignores_case(): void
    {
        Movie::create(['title' => 'Island Python', 'tags' => ',monster,']);

        $this->get('/?q=island')->assertOk()->assertSee('Island Python');
        $this->get('/?q=MONSTER')->assertOk()->assertSee('Island Python');
    }
}
