<?php

use App\Http\Controllers\MovieController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StatsController;
use App\Models\Movie;
use App\Services\MediaOptimizer;
use App\Support\EmbedUrl;
use App\Support\MediaStore;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Admin routes FIRST so /movies/create is not swallowed by /movies/{movie}.
Route::middleware(['throttle:60,1', 'admin'])->group(function () {
    Route::get('/admin/stats', [StatsController::class, 'index'])->name('admin.stats');
    Route::get('/admin/incomplete', [MovieController::class, 'incomplete'])->name('movies.incomplete');
    Route::resource('movies', MovieController::class)->except(['index', 'show']);
    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::delete('/requests/{movieRequest}', [RequestController::class, 'destroy'])->name('requests.destroy');
});

Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/robots.txt', [SeoController::class, 'robots']);

// Public pages: counted by the visitor stats (see TrackVisit).
Route::middleware('track')->group(function () {
    Route::get('/', [MovieController::class, 'index'])->name('home');
    Route::get('/suggestions', [MovieController::class, 'suggestions'])->name('suggestions');
    Route::get('/request', [RequestController::class, 'create'])->name('request.create');
    Route::resource('movies', MovieController::class)->only(['index', 'show']);
});

Route::get('/search/live', [MovieController::class, 'liveSearch'])->middleware('throttle:60,1')->name('search.live');
Route::post('/request', [RequestController::class, 'store'])->middleware('throttle:5,1')->name('request.store');
Route::get('/api/v1/movies/{movie}', [MovieController::class, 'apiShow'])->middleware('throttle:60,1');

// Public Incomplete / Upcoming Trailers endpoint for Mobile App
Route::get('/api/v1/incomplete', function () {
    $items = Movie::query()
        ->withCount('episodes')
        ->with('episodes:id,movie_id,number,subtitle_url')
        ->latest('id')
        ->take(60)
        ->get()
        ->filter(fn (Movie $m) => $m->isIncomplete())
        ->take(20)
        ->values()
        ->map(fn (Movie $m) => [
            'id' => $m->id,
            'title' => $m->title,
            'image' => $m->image,
            'year' => $m->year,
            'type' => $m->type,
            'genre' => $m->genre,
            'language' => $m->language,
            'tags' => $m->tags,
            'watch_with_sub' => (bool) $m->watch_with_sub,
            'is_incomplete' => true,
            'episodes_count' => (int) ($m->episodes_count ?? 0),
            'trailer_embed_url' => $m->trailer_embed_url,
            'trailer_url' => $m->trailer_url,
        ]);

    return response()->json(['data' => $items]);
})->middleware('throttle:60,1');

// Flutter Admin API endpoints in web.php
Route::prefix('api/v1')
    ->middleware('throttle:60,1')
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    ])
    ->group(function () {
        Route::post('/auth/login', function (Request $request) {
            $data = $request->validate([
                'email' => ['required', 'string'],
                'password' => ['required', 'string'],
            ]);

            $adminUser = (string) config('auracinema.admin_user', env('ADMIN_USER', ''));
            $adminPassHash = (string) config('auracinema.admin_password_hash', env('ADMIN_PASSWORD_HASH', ''));

            if (
                $adminUser === '' ||
                $adminPassHash === '' ||
                ! hash_equals($adminUser, trim($data['email'])) ||
                ! Hash::check($data['password'], $adminPassHash)
            ) {
                return response()->json(['message' => 'Invalid username or password'], 401);
            }

            $token = Str::random(64);
            Cache::put("auracinema_api_token:{$token}", true, now()->addDays(14));

            return response()->json([
                'token' => $token,
                'user' => [
                    'name' => $adminUser,
                ],
            ]);
        });

        $checkAdminToken = function (Request $request): bool {
            $header = (string) $request->header('Authorization', '');
            $token = str_starts_with($header, 'Bearer ')
                ? trim(substr($header, 7))
                : '';

            if ($token === '') {
                return false;
            }

            return (bool) Cache::get("auracinema_api_token:{$token}", false);
        };

        $clearDotnetCache = function (): void {
            $configured = rtrim((string) config('auracinema.dotnet_api_url', env('DOTNET_API_URL', '')), '/');
            $adminSecret = (string) config('auracinema.admin_secret', env('ADMIN_SECRET', 'AuraCinema_Admin_Master_999'));
            $apiKey = (string) config('auracinema.dotnet_api_key', env('DOTNET_API_KEY', 'AuraCinema_Secret_Key_2026'));

            $headers = [
                'X-Api-Key' => $apiKey,
                'X-Admin-Secret' => $adminSecret,
            ];

            $hosts = array_values(array_unique(array_filter([
                $configured,
                'http://172.17.0.1:5050',
                'http://172.31.13.67:5050',
                'http://127.0.0.1:5050',
            ])));

            foreach ($hosts as $baseUrl) {
                try {
                    $res = Http::timeout(2)
                        ->withHeaders($headers)
                        ->post("{$baseUrl}/api/v1/admin/clear-cache");

                    if ($res->successful()) {
                        break;
                    }
                } catch (\Throwable $e) {
                    // Try next reachable Docker/host IP
                }
            }
        };

        $storePosterToR2 = function (
            Request $request,
            MediaOptimizer $media,
            string $title,
            $year,
            ?string $imageUrl,
            ?string $oldImage = null
        ): ?string {
            $newPath = null;
            $baseName = MediaStore::baseName($title, $year);

            if ($request->hasFile('image')) {
                $newPath = $media->storePoster($request->file('image'), $baseName);
            } elseif (filled($imageUrl)) {
                $rawUrl = trim((string) $imageUrl);
                try {
                    $imgRes = Http::timeout(10)->get($rawUrl);
                    if ($imgRes->successful() && strlen($imgRes->body()) > 500) {
                        $tmpPath = tempnam(sys_get_temp_dir(), 'poster_');
                        if ($tmpPath !== false) {
                            file_put_contents($tmpPath, $imgRes->body());
                            try {
                                $mime = $imgRes->header('Content-Type') ?: 'image/jpeg';
                                $uploaded = new UploadedFile($tmpPath, 'poster.jpg', $mime, null, true);
                                $newPath = $media->storePoster($uploaded, $baseName);
                            } finally {
                                @unlink($tmpPath);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // Ignore remote poster fetch error
                }
            }

            if ($newPath !== null && filled($oldImage)) {
                MediaStore::forget($oldImage);
            }

            return $newPath;
        };

        Route::get('/admin/movies', function (Request $request) use ($checkAdminToken) {
            if (! $checkAdminToken($request)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $q = trim((string) $request->query('q', ''));
            $query = Movie::query()->latest('id');

            if ($q !== '') {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(function ($w) use ($like) {
                    $w->where('title', 'like', $like)
                        ->orWhere('tags', 'like', $like);
                });
            }

            return response()->json([
                'data' => $query->limit(100)->get([
                    'id',
                    'title',
                    'type',
                    'genre',
                    'year',
                    'language',
                    'tags',
                    'watch_with_sub',
                    'image',
                ]),
            ]);
        });

        // Create a new movie from Flutter Mobile Admin
        Route::post('/admin/movies', function (Request $request, MediaOptimizer $media) use ($checkAdminToken, $clearDotnetCache, $storePosterToR2) {
            if (! $checkAdminToken($request)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $data = $request->validate([
                'type' => ['nullable', 'string', 'max:50'],
                'language' => ['nullable', 'string', 'max:100'],
                'genre' => ['nullable', 'string', 'max:100'],
                'year' => ['nullable', 'string', 'max:20'],
                'title' => ['required', 'string', 'max:255'],
                'image_url' => ['nullable', 'string', 'max:1000'],
                'image' => ['nullable', 'image', 'max:10240'],
                'tags' => ['nullable', 'string', 'max:500'],
                'description' => ['nullable', 'string', 'max:5000'],
                'trailer_embed_url' => ['nullable', 'string', 'max:500'],
                'watch_with_sub' => ['nullable', 'boolean'],
                'stream_url' => ['nullable', 'string', 'max:500'],
                'embed_url' => ['nullable', 'string', 'max:500'],
                'download_480p' => ['nullable', 'string', 'max:500'],
                'download_720p' => ['nullable', 'string', 'max:500'],
                'download_1080p' => ['nullable', 'string', 'max:500'],
                'telegram_url' => ['nullable', 'string', 'max:500'],
                'subtitle_url' => ['nullable', 'string', 'max:500'],
            ]);

            $imagePath = $storePosterToR2(
                $request,
                $media,
                $data['title'],
                $data['year'] ?? null,
                $data['image_url'] ?? null,
                null
            );
            unset($data['image_url'], $data['image']);

            $data['type'] = filled($data['type'] ?? null) ? trim($data['type']) : 'movie';
            $data['language'] = filled($data['language'] ?? null) ? trim($data['language']) : null;
            $data['genre'] = filled($data['genre'] ?? null) ? trim($data['genre']) : null;
            $data['year'] = filled($data['year'] ?? null) ? trim($data['year']) : null;
            $data['tags'] = Movie::normalizeTags($data['tags'] ?? '');
            $data['watch_with_sub'] = filter_var($data['watch_with_sub'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $data['image'] = $imagePath;

            if (filled($data['embed_url'] ?? null)) {
                $data['embed_url'] = EmbedUrl::normalize($data['embed_url']) ?? $data['embed_url'];
            }
            if (filled($data['trailer_embed_url'] ?? null)) {
                $data['trailer_embed_url'] = EmbedUrl::normalize($data['trailer_embed_url']) ?? $data['trailer_embed_url'];
            }
            if (filled($data['telegram_url'] ?? null)) {
                $data['telegram_added_at'] = now();
            }

            $movie = Movie::create($data);

            $clearDotnetCache();

            return response()->json([
                'message' => 'Created',
                'id' => $movie->id,
                'image' => $movie->image,
                'poster_url' => $movie->poster_url,
            ], 201);
        });

        Route::get('/admin/movies/{movie}', function (Request $request, Movie $movie) use ($checkAdminToken) {
            if (! $checkAdminToken($request)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            return response()->json([
                'id' => $movie->id,
                'type' => $movie->type,
                'language' => $movie->language,
                'genre' => $movie->genre,
                'year' => $movie->year,
                'title' => $movie->title,
                'tags' => $movie->tags,
                'description' => $movie->description,
                'trailer_embed_url' => $movie->trailer_embed_url,
                'watch_with_sub' => (bool) $movie->watch_with_sub,
                'stream_url' => $movie->stream_url,
                'embed_url' => $movie->embed_url,
                'download_480p' => $movie->download_480p,
                'download_720p' => $movie->download_720p,
                'download_1080p' => $movie->download_1080p,
                'telegram_url' => $movie->telegram_url,
                'subtitle_url' => $movie->subtitle_url,
                'image' => $movie->image,
                'poster_url' => $movie->poster_url,
            ]);
        });

        Route::match(['put', 'post'], '/admin/movies/{movie}', function (Request $request, Movie $movie, MediaOptimizer $media) use ($checkAdminToken, $clearDotnetCache, $storePosterToR2) {
            if (! $checkAdminToken($request)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $data = $request->validate([
                'type' => ['nullable', 'string'],
                'language' => ['nullable', 'string', 'max:100'],
                'genre' => ['nullable', 'string', 'max:100'],
                'year' => ['nullable', 'string', 'max:20'],
                'title' => ['required', 'string', 'max:255'],
                'image_url' => ['nullable', 'string', 'max:1000'],
                'image' => ['nullable', 'image', 'max:10240'],
                'tags' => ['nullable', 'string', 'max:500'],
                'description' => ['nullable', 'string', 'max:5000'],
                'trailer_embed_url' => ['nullable', 'string', 'max:500'],
                'watch_with_sub' => ['nullable', 'boolean'],
                'stream_url' => ['nullable', 'string', 'max:500'],
                'embed_url' => ['nullable', 'string', 'max:500'],
                'download_480p' => ['nullable', 'string', 'max:500'],
                'download_720p' => ['nullable', 'string', 'max:500'],
                'download_1080p' => ['nullable', 'string', 'max:500'],
                'telegram_url' => ['nullable', 'string', 'max:500'],
                'subtitle_url' => ['nullable', 'string', 'max:500'],
            ]);

            $newPoster = $storePosterToR2(
                $request,
                $media,
                $data['title'],
                $data['year'] ?? $movie->year,
                $data['image_url'] ?? null,
                $movie->image
            );
            unset($data['image_url'], $data['image']);
            if ($newPoster !== null) {
                $data['image'] = $newPoster;
            }

            if (array_key_exists('language', $data)) {
                $data['language'] = filled($data['language']) ? trim($data['language']) : null;
            }
            if (array_key_exists('genre', $data)) {
                $data['genre'] = filled($data['genre']) ? trim($data['genre']) : null;
            }
            if (array_key_exists('year', $data)) {
                $data['year'] = filled($data['year']) ? trim($data['year']) : null;
            }
            if (array_key_exists('tags', $data)) {
                $data['tags'] = Movie::normalizeTags($data['tags']);
            }
            if (array_key_exists('watch_with_sub', $data)) {
                $data['watch_with_sub'] = filter_var($data['watch_with_sub'], FILTER_VALIDATE_BOOLEAN);
            }
            if (array_key_exists('embed_url', $data) && filled($data['embed_url'])) {
                $data['embed_url'] = EmbedUrl::normalize($data['embed_url']) ?? $data['embed_url'];
            }
            if (array_key_exists('trailer_embed_url', $data) && filled($data['trailer_embed_url'])) {
                $data['trailer_embed_url'] = EmbedUrl::normalize($data['trailer_embed_url']) ?? $data['trailer_embed_url'];
            }

            $movie->update($data);

            $clearDotnetCache();

            return response()->json([
                'message' => 'Updated',
                'id' => $movie->id,
                'language' => $movie->language,
                'tags' => $movie->tags,
                'image' => $movie->image,
                'poster_url' => $movie->poster_url,
            ]);
        });

        // Delete a movie from Flutter Mobile Admin
        Route::delete('/admin/movies/{movie}', function (Request $request, Movie $movie) use ($checkAdminToken, $clearDotnetCache) {
            if (! $checkAdminToken($request)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $files = array_merge(
                [$movie->image, $movie->trailer_url],
                is_array($movie->gallery) ? $movie->gallery : [],
                collect(is_array($movie->cast_members) ? $movie->cast_members : [])->pluck('photo')->all(),
            );

            $movie->episodes()->delete();
            $movie->delete();

            foreach (array_filter($files) as $path) {
                MediaStore::forget($path);
            }

            $clearDotnetCache();

            return response()->json([
                'message' => 'Deleted',
            ]);
        });
    });