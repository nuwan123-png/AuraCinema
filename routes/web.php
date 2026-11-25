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

        Route::get('/admin/movies/{movie}', function (Request $request, Movie $movie) use ($checkAdminToken) {
            if (! $checkAdminToken($request)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            return response()->json([
                'id' => $movie->id,
                'type' => $movie->type,
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
            ]);
        });

        Route::put('/admin/movies/{movie}', function (Request $request, Movie $movie) use ($checkAdminToken) {
            if (! $checkAdminToken($request)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $data = $request->validate([
                'type' => ['nullable', 'string'],
                'title' => ['required', 'string', 'max:255'],
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

            if (array_key_exists('tags', $data)) {
                $data['tags'] = Movie::normalizeTags($data['tags']);
            }
            if (array_key_exists('embed_url', $data) && filled($data['embed_url'])) {
                $data['embed_url'] = EmbedUrl::normalize($data['embed_url']) ?? $data['embed_url'];
            }
            if (array_key_exists('trailer_embed_url', $data) && filled($data['trailer_embed_url'])) {
                $data['trailer_embed_url'] = EmbedUrl::normalize($data['trailer_embed_url']) ?? $data['trailer_embed_url'];
            }

            $movie->update($data);

            return response()->json([
                'message' => 'Updated',
                'id' => $movie->id,
                'tags' => $movie->tags,
            ]);
        });
    });