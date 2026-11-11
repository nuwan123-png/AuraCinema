<?php

namespace Tests\Feature;

use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.r2_backup.bucket' => 'test-bucket']);
        Storage::fake('r2_backup');
    }

    public function test_backup_then_restore_brings_the_data_back_with_ids(): void
    {
        $a = Movie::create(['title' => 'Island Python', 'description' => 'සිංහල text', 'tags' => ',monster,']);
        $b = Movie::create(['title' => 'Sigma']);
        $b->episodes()->create(['number' => 1, 'url' => 'https://example.com/e1']);

        $this->artisan('db:backup')->assertSuccessful();
        $this->assertCount(1, Storage::disk('r2_backup')->files());

        foreach (['episodes', 'movie_requests', 'movies'] as $t) {
            DB::table($t)->delete();
        }

        $this->artisan('db:restore')->assertSuccessful();

        $this->assertSame(2, Movie::count());
        $this->assertSame('සිංහල text', Movie::find($a->id)->description);
        $this->assertSame(1, DB::table('episodes')->count());
        $this->assertGreaterThan($b->id, Movie::create(['title' => 'New'])->id);
    }

    public function test_restore_refuses_when_tables_are_not_empty(): void
    {
        Movie::create(['title' => 'A']);
        $this->artisan('db:backup')->assertSuccessful();

        $this->artisan('db:restore')->assertFailed();
        $this->assertSame(1, Movie::count());
    }

    public function test_only_the_newest_backups_are_kept(): void
    {
        $disk = Storage::disk('r2_backup');
        foreach (['2026-01-01', '2026-01-02', '2026-01-03'] as $d) {
            $disk->put("db-{$d}_000000.json.gz", 'old');
        }

        $this->artisan('db:backup --keep=2')->assertSuccessful();

        $files = collect($disk->files())->sort()->values();
        $this->assertCount(2, $files);
        $this->assertStringNotContainsString('2026-01-01', $files->implode(','));
    }

    public function test_needs_the_bucket_setting(): void
    {
        config(['filesystems.disks.r2_backup.bucket' => null]);

        $this->artisan('db:backup')->assertFailed();
    }
}
