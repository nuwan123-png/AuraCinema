<?php

use App\Http\Controllers\MovieController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StatsController;
use App\Models\Movie;
use App\Support\EmbedUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
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
            $baseUrl = rtrim((string) config('auracinema.dotnet_api_url', env('DOTNET_API_URL', 'http://127.0.0.1:5050')), '/');
            $secret = (string) config('auracinema.admin_secret', env('ADMIN_SECRET', ''));

            if ($baseUrl === '') {
                return;
            }

            try {
                $req = Http::timeout(2);
                if ($secret !== '') {
                    $req = $req->withHeaders(['X-Admin-Secret' => $secret]);
                }
                $req->post("{$baseUrl}/admin/clear-cache");
            } catch (\Throwable $e) {
                // Silent fallback if .NET API is restarting
            }
        };

        $storePosterImage = function (Request $request, ?string $imageUrl, ?string $oldImage = null): ?string {
            $newPath = null;

            if ($request->hasFile('image')) {
                $newPath = $request->file('image')->store('posters', 'public');
            } elseif (filled($imageUrl)) {
                $rawUrl = trim((string) $imageUrl);
                try {
                    $imgRes = Http::timeout(10)->get($rawUrl);
                    if ($imgRes->successful() && strlen($imgRes->body()) > 500) {
                        $ext = pathinfo((string) parse_url($rawUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                        $ext = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp']) ? strtolower($ext) : 'jpg';
                        $filename = 'posters/'.Str::random(24).'.'.$ext;
                        Storage::disk('public')->put($filename, $imgRes->body());
                        $newPath = $filename;
                    }
                } catch (\Throwable $e) {
                    // Ignore poster download error
                }
            }

            if ($newPath !== null && filled($oldImage) && ! str_starts_with($oldImage, 'http')) {
                Storage::disk('public')->delete($oldImage);
            }

            return $newPath;
        };

        Route::get('/admin/movies', function (Request $request) use ($checkAdminToken) {
            if (! $checkAdminToken($request)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $q = trim((string) $request->query('q', ''));
            $query = Movie::query()->with('episodes:id,movie_id,number,subtitle_url')->withCount('episodes')->latest('id');

            if ($q !== '') {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(function ($w) use ($like) {
                    $w->where('title', 'like', $like)
                        ->orWhere('tags', 'like', $like);
                });
            }

            $movies = $query->limit(100)->get([
                'id',
                'title',
                'type',
                'genre',
                'year',
                'language',
                'tags',
                'watch_with_sub',
                'image',
                'description',
                'embed_url',
                'download_480p',
                'download_720p',
                'download_1080p',
                'subtitle_url',
            ]);

            return response()->json([
                'data' => $movies->map(function (Movie $m) {
                    return [
                        'id' => $m->id,
                        'title' => $m->title,
                        'type' => $m->type,
                        'genre' => $m->genre,
                        'year' => $m->year,
                        'language' => $m->language,
                        'tags' => $m->tags,
                        'watch_with_sub' => (bool) $m->watch_with_sub,
                        'image' => $m->image,
                        // Admin-only data-quality helper: which required fields are still empty.
                        'missing_fields' => $m->missingFields(),
                    ];
                }),
            ]);
        });

        // Create a new movie from Flutter Mobile Admin
        Route::post('/admin/movies', function (Request $request) use ($checkAdminToken, $clearDotnetCache, $storePosterImage) {
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
                'image' => ['nullable', 'image', 'max:5120'],
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

            $imagePath = $storePosterImage($request, $data['image_url'] ?? null, null);
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
            ], 201);
        });

        Route::get('/admin/movies/{movie}', function (Request $request, Movie $movie) use ($checkAdminToken) {
            if (! $checkAdminToken($request)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $posterUrl = null;
            if (filled($movie->image)) {
                $posterUrl = str_starts_with($movie->image, 'http')
                    ? $movie->image
                    : rtrim((string) config('auracinema.site_url', url('/')), '/').'/storage/'.ltrim($movie->image, '/');
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
                'poster_url' => $posterUrl,
                // Admin-only data-quality helper: which required fields are still empty.
                'missing_fields' => $movie->missingFields(),
            ]);
        });

        Route::match(['put', 'post'], '/admin/movies/{movie}', function (Request $request, Movie $movie) use ($checkAdminToken, $clearDotnetCache, $storePosterImage) {
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
                'image' => ['nullable', 'image', 'max:5120'],
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

            $newPoster = $storePosterImage($request, $data['image_url'] ?? null, $movie->image);
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
            ]);
        });
    });