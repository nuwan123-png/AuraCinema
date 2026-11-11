<?php

namespace Tests\Feature;

use App\Support\MediaStore;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStoreTest extends TestCase
{
    public function test_local_mode_serves_from_storage_and_publish_is_a_noop(): void
    {
        config(['auracinema.media_disk' => 'public']);
        Storage::fake('public')->put('posters/a.webp', 'x');

        $this->assertTrue(MediaStore::publish('posters/a.webp'));
        $this->assertStringEndsWith('/storage/posters/a.webp', MediaStore::url('posters/a.webp'));
        $this->assertTrue(Storage::disk('public')->exists('posters/a.webp'));
    }

    public function test_r2_mode_moves_file_and_serves_r2_url(): void
    {
        config(['auracinema.media_disk' => 'r2', 'auracinema.media_url' => 'https://pub-test.r2.dev/']);
        Storage::fake('public')->put('trailers/t.mp4', 'video');
        Storage::fake('r2');

        $this->assertStringEndsWith('/storage/trailers/t.mp4', MediaStore::url('trailers/t.mp4'));

        $this->assertTrue(MediaStore::publish('trailers/t.mp4'));
        Storage::disk('r2')->assertExists('trailers/t.mp4');
        Storage::disk('public')->assertMissing('trailers/t.mp4');
        $this->assertSame('https://pub-test.r2.dev/trailers/t.mp4', MediaStore::url('trailers/t.mp4'));
    }

    public function test_forget_removes_from_both_disks(): void
    {
        config(['auracinema.media_disk' => 'r2']);
        Storage::fake('public')->put('posters/b.webp', 'x');
        Storage::fake('r2')->put('posters/b.webp', 'x');

        MediaStore::forget('posters/b.webp');

        Storage::disk('public')->assertMissing('posters/b.webp');
        Storage::disk('r2')->assertMissing('posters/b.webp');
    }

    public function test_null_path_gives_null_url(): void
    {
        $this->assertNull(MediaStore::url(null));
    }
}
