<?php

namespace App\Http\Controllers;

use App\Jobs\OptimizeTrailer;
use App\Models\Episode;
use App\Models\Movie;
use App\Services\MediaOptimizer;
use App\Support\EmbedUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Support\MediaStore;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MovieController extends Controller
{
    private const MAX_GALLERY = 12;

    public function __construct(private MediaOptimizer $media) {}

    public function liveSearch(Request $request)
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 80);
        if (mb_strlen($q) < 2) {
            return response()->json(['items' => [], 'total' => 0]);
        }

        $like = '%'.addcslashes($q, '%_\\').'%';
        $op = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = Movie::query()->where(fn ($w) => $w->where('title', $op, $like)->orWhere('tags', $op, $like));
        $total = (clone $query)->count();

        $items = $query->orderByRaw('CASE WHEN title '.$op.' ? THEN 0 ELSE 1 END', [addcslashes($q, '%_\\').'%'])
            ->latest('id')
            ->limit(6)
            ->get(['id', 'title', 'year', 'type', 'genre', 'language', 'image'])
            ->map(fn (Movie $m) => [
                'title' => $m->title,
                'year' => $m->year,
                'meta' => collect([$m->type_label, $m->genre, $m->language])->filter()->implode(' · '),
                'poster' => $m->image ? $m->poster_url : null,
                'url' => route('movies.show', $m),
            ]);

        return response()->json(['items' => $items, 'total' => $total]);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');
        $genre = (string) $request->query('genre', '');
        $year = (int) $request->query('year', 0);
        $language = (string) $request->query('language', '');
        $tag = Movie::normalizeTag((string) $request->query('tag', ''));

        $type = array_key_exists($type, config('auracinema.types')) ? $type : '';
        $genre = in_array($genre, config('auracinema.genres'), true) ? $genre : '';
        $language = in_array($language, config('auracinema.languages'), true) ? $language : '';
        $year = ($year >= 1900 && $year <= 2100) ? $year : 0;

        if ($q !== '' || $type !== '' || $genre !== '' || $year !== 0 || $language !== '' || $tag !== '') {
            $query = Movie::query()->withCount('episodes');

            if ($q !== '') {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $op = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $query->where(fn ($w) => $w->where('title', $op, $like)->orWhere('tags', $op, $like));
            }
            if ($type !== '') {
                $query->where('type', $type);
            }
            if ($genre !== '') {
                $query->where('genre', $genre);
            }
            if ($language !== '') {
                $langTag = '%,'.addcslashes(Movie::normalizeTag($language), '%_\\').',%';
                $query->where(fn ($w) => $w->where('language', $language)->orWhere('tags', 'like', $langTag));
            }
            if ($year !== 0) {
                $query->where('year', $year);
            }
            if ($tag !== '') {
                $query->where('tags', 'like', '%,'.addcslashes($tag, '%_\\').',%');
            }

            $heading = $q !== ''
                ? 'Results for “'.$q.'”'
                : collect([
                    $language, $genre, $year ?: null, $tag !== '' ? '#'.$tag : null,
                    $type !== '' ? match ($type) {
                        'movie' => 'Movies', 'series' => 'Drama / Series', default => 'Anime',
                    } : null,
                ])->filter()->implode(' · ');

            return view('movies.browse', [
                'heading' => $heading,
                'results' => $query->latest()->simplePaginate(24)->withQueryString(),
            ]);
        }

        $recent = Movie::withCount('episodes')->latest()->take(40)->get();

        return view('movies.index', [
            'heroes' => $recent->filter(fn ($m) => $m->image)->take(5)->values(),
            'latest' => $recent->take(20),
            // 'withTrailers' වෙනුවට දැන් යවන්නේ incomplete චිත්‍රපට පමණයි (ඒවා "Watch the trailers" පේළියේ දිස්වේවි)
            'withTrailers' => $recent->filter(fn ($m) => $m->isIncomplete())->take(20)->values(),
            // "Full movies" lists every title, newest first, so a movie you add shows up here straight away.
            'fullMovies' => Movie::withCount('episodes')->latest()->limit(200)->get(),
        ]);
    }

    public function suggestions()
    {
        return view('movies.browse', [
            'heading' => 'Suggestions for you',
            'results' => Movie::withCount('episodes')->inRandomOrder()->limit(24)->get(),
        ]);
    }

    public function create()
    {
        return view('movies.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $data = $this->movieData($validated);

        $created = [];
        $orphans = [];
        try {
            if ($request->hasFile('image')) {
                $data['image'] = $this->media->storePoster($request->file('image'), MediaStore::baseName($validated['title'], $validated['year'] ?? null));
                $created[] = $data['image'];
            }
            if ($request->hasFile('trailer')) {
                $data['trailer_url'] = $this->storeTrailer($request, $validated);
                $created[] = $data['trailer_url'];
            }
            $data['gallery'] = $this->buildGallery($request, $validated, null, $created, $orphans) ?: null;
            $data['cast_members'] = $this->buildCast($request, $validated, null, $created, $orphans) ?: null;

            $movie = DB::transaction(function () use ($data, $validated) {
                $movie = Movie::create($data);
                $this->syncEpisodes($movie, $validated['episodes'] ?? []);
                return $movie;
            });
        } catch (\Throwable $e) {
            array_map([MediaStore::class, 'forget'], $created);
            throw $e;
        }

        if ($request->hasFile('trailer')) {
            OptimizeTrailer::dispatchAfterResponse($movie->id, $data['trailer_url']);
        }

        return redirect()->route('movies.show', $movie)->with('success', 'Movie added. The trailer is being compressed in the background.');
    }

    private function storeTrailer(Request $request, array $validated): string
    {
        $ext = strtolower($request->file('trailer')->getClientOriginalExtension());
        $ext = in_array($ext, ['mp4', 'webm', 'mov', 'mkv'], true) ? $ext : 'mp4';
        return $request->file('trailer')->storeAs('trailers', MediaStore::baseName($validated['title'], $validated['year'] ?? null).'.'.$ext, 'public');
    }

    public function incomplete()
    {
        $all = Movie::query()
            ->withCount('episodes')
            ->with('episodes:id,movie_id,number,subtitle_url')
            ->latest('id')
            ->get();

        $movies = $all->filter(fn (Movie $m) => $m->isIncomplete())->values();
        return view('movies.incomplete', ['movies' => $movies, 'total' => $all->count()]);
    }

    public function show(Movie $movie)
    {
        $movie->load('episodes');
        return view('movies.show', compact('movie'));
    }

    public function edit(Movie $movie)
    {
        $movie->load('episodes');
        return view('movies.edit', compact('movie'));
    }

    public function update(Request $request, Movie $movie)
    {
        $validated = $this->validated($request);
        $data = $this->movieData($validated, $movie);

        $oldImage = $movie->image;
        $oldTrailer = $movie->trailer_url;
        $created = [];
        $orphans = [];
        $newImage = null;
        $newTrailer = null;

        try {
            if ($request->hasFile('image')) {
                $newImage = $this->media->storePoster($request->file('image'), MediaStore::baseName($validated['title'], $validated['year'] ?? null));
                $data['image'] = $newImage;
                $created[] = $newImage;
            }
            if ($request->hasFile('trailer')) {
                $newTrailer = $this->storeTrailer($request, $validated);
                $data['trailer_url'] = $newTrailer;
                $created[] = $newTrailer;
            }
            $data['gallery'] = $this->buildGallery($request, $validated, $movie, $created, $orphans) ?: null;
            $data['cast_members'] = $this->buildCast($request, $validated, $movie, $created, $orphans) ?: null;

            DB::transaction(function () use ($movie, $data, $validated) {
                $movie->update($data);
                $this->syncEpisodes($movie, $validated['episodes'] ?? []);
            });
        } catch (\Throwable $e) {
            array_map([MediaStore::class, 'forget'], $created);
            throw $e;
        }

        if ($newImage) { $orphans[] = $oldImage; }
        if ($newTrailer) { $orphans[] = $oldTrailer; }
        array_map([MediaStore::class, 'forget'], array_filter($orphans));

        if ($newTrailer) {
            OptimizeTrailer::dispatchAfterResponse($movie->id, $newTrailer);
        }

        return redirect()->route('movies.show', $movie)->with('success', 'Movie updated.');
    }

    public function destroy(Movie $movie)
    {
        $files = array_merge(
            [$movie->image, $movie->trailer_url],
            $movie->gallery ?: [],
            collect($movie->cast_members ?: [])->pluck('photo')->all(),
        );
        $movie->delete();

        foreach (array_filter($files) as $path) {
            MediaStore::forget($path);
        }

        return redirect()->route('movies.index')->with('success', 'Movie deleted.');
    }

    private function buildGallery(Request $request, array $validated, ?Movie $movie, array &$created, array &$orphans): array
    {
        $current = array_values(array_filter((array) ($movie?->gallery ?: []), 'is_string'));
        $remove = array_values(array_intersect((array) ($validated['gallery_remove'] ?? []), $current));
        $keep = array_values(array_diff($current, $remove));
        $files = array_values(array_filter((array) $request->file('gallery_new')));

        if (count($keep) + count($files) > self::MAX_GALLERY) {
            throw ValidationException::withMessages(['gallery_new' => 'A gallery can hold at most '.self::MAX_GALLERY.' pictures.']);
        }

        foreach ($files as $file) {
            $path = $this->media->storeGalleryImage($file, MediaStore::baseName($validated['title'], $validated['year'] ?? null));
            $created[] = $path;
            $keep[] = $path;
        }
        $orphans = array_merge($orphans, $remove);

        return $keep;
    }

    private function buildCast(Request $request, array $validated, ?Movie $movie, array &$created, array &$orphans): array
    {
        $currentPhotos = collect($movie?->cast_members ?: [])->pluck('photo')->filter()->values()->all();
        $kept = [];
        $members = [];

        foreach (($validated['cast'] ?? []) as $key => $row) {
            $photo = null;
            $upload = $request->file("cast.$key.photo");
            $existing = $row['existing_photo'] ?? null;

            if ($upload) {
                $photo = $this->media->storeCastPhoto($upload, MediaStore::baseName($row['name'], null));
                $created[] = $photo;
            } elseif ($existing && in_array($existing, $currentPhotos, true) && ! in_array($existing, $kept, true)) {
                $photo = $existing;
            }

            if ($photo && ! in_array($photo, $created, true)) {
                $kept[] = $photo;
            }
            $members[] = ['name' => trim($row['name']), 'role' => trim((string) ($row['role'] ?? '')), 'photo' => $photo];
        }

        $orphans = array_merge($orphans, array_values(array_diff($currentPhotos, $kept)));

        return $members;
    }

    private function validated(Request $request): array
    {
        $hasEpisodes = $request->boolean('has_episodes');

        $rows = $hasEpisodes
            ? collect($request->input('episodes', []))
                ->filter(fn ($row) => is_array($row) && filled($row['url'] ?? null))
                ->map(function ($row) {
                    if (!empty($row['download_url'])) {
                        $row['download_url'] = str_replace('/e/', '/v/', $row['download_url']);
                    }
                    return $row;
                })
                ->values()
                ->all()
            : [];
        $request->merge(['episodes' => $rows]);

        $castRows = collect($request->input('cast', []))
            ->filter(fn ($row, $key) => is_array($row) && (filled($row['name'] ?? null) || filled($row['role'] ?? null)
                || filled($row['existing_photo'] ?? null) || $request->hasFile("cast.$key.photo")))
            ->all();
        $request->merge(['cast' => $castRows]);

        $embed = trim((string) $request->input('embed_url', ''));
        if ($embed !== '') {
            $request->merge(['embed_url' => EmbedUrl::normalize($embed) ?? $embed]);
        }
        
        foreach (['download_480p', 'download_720p', 'download_1080p'] as $field) {
            $dlLink = trim((string) $request->input($field, ''));
            if ($dlLink !== '') {
                $request->merge([$field => str_replace('/e/', '/v/', $dlLink)]);
            }
        }

        $validated = $request->validate($this->rules());

        if ($hasEpisodes) {
            $numbers = collect($validated['episodes'] ?? [])->pluck('number');
            if ($numbers->isEmpty()) {
                throw ValidationException::withMessages(['episodes' => 'Add at least one episode link, or untick "This title has episodes".']);
            }
            if ($numbers->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['episodes' => 'Each episode number can be used only once.']);
            }
        }

        return $validated;
    }

    private function movieData(array $validated, ?Movie $movie = null): array
    {
        $data = Arr::only($validated, [
            'title', 'description', 'stream_url', 'embed_url', 'genre', 'year', 'language',
            'download_480p', 'download_720p', 'download_1080p' , 'subtitle_url'
        ]);
        
        // "Completed" only makes sense for a title with episodes.
        $data['is_completed'] = ! empty($validated['episodes']) && ! empty($validated['is_completed']);
        $data['tags'] = Movie::normalizeTags($validated['tags'] ?? null);
        $data['type'] = $validated['type'] ?? $movie?->type ?? 'movie';

        if (! empty($validated['episodes'])) {
            $data['stream_url'] = null;
        }

        return $data;
    }

    private function syncEpisodes(Movie $movie, array $episodes): void
    {
        $movie->episodes()->delete();

        if ($episodes === []) {
            return;
        }

        $now = now();
        Episode::insert(
            collect($episodes)
                ->map(fn ($row) => [
                    'movie_id' => $movie->id,
                    'number' => (int) $row['number'],
                    'url' => $row['url'],
                    'subtitle_url' => filled($row['subtitle_url'] ?? null) ? $row['subtitle_url'] : null,
                    'download_url' => filled($row['download_url'] ?? null) ? $row['download_url'] : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->sortBy('number')
                ->values()
                ->all()
        );
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'trailer' => ['nullable', 'mimes:mp4,mkv,webm,mov', 'max:30720'],
            'stream_url' => ['nullable', 'url:http,https', 'max:500'],
            'embed_url' => ['nullable', 'string', 'max:500', function (string $attribute, mixed $value, \Closure $fail) {
                if (EmbedUrl::normalize($value) === null) {
                    $fail('Watch now must be a YouTube, Vimeo, Bunny Stream or Cloudflare Stream player link (https).');
                }
            }],
            'type' => ['nullable', Rule::in(array_keys(config('auracinema.types')))],
            'genre' => ['nullable', Rule::in(config('auracinema.genres'))],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'language' => ['nullable', Rule::in(config('auracinema.languages'))],
            'tags' => ['nullable', 'string', 'max:300'],
            'is_completed' => ['nullable', 'boolean'],
            'episodes' => ['nullable', 'array', 'max:500'],
            'episodes.*.number' => ['required', 'integer', 'between:1,9999'],
            'episodes.*.url' => ['required', 'url:http,https', 'max:500'],
            'episodes.*.subtitle_url' => ['nullable', 'url:http,https', 'max:500'],
            'episodes.*.download_url' => ['nullable', 'url:http,https', 'max:500'],
            'download_480p' => ['nullable', 'url:http,https', 'max:500'],
            'download_720p' => ['nullable', 'url:http,https', 'max:500'],
            'download_1080p' => ['nullable', 'url:http,https', 'max:500'],
            'subtitle_url' => ['nullable', 'url:http,https', 'max:500'],
            'gallery_new' => ['nullable', 'array', 'max:'.self::MAX_GALLERY],
            'gallery_new.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'gallery_remove' => ['nullable', 'array'],
            'gallery_remove.*' => ['string', 'max:255'],
            'cast' => ['nullable', 'array', 'max:24'],
            'cast.*.name' => ['required', 'string', 'max:80'],
            'cast.*.role' => ['nullable', 'string', 'max:80'],
            'cast.*.photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'cast.*.existing_photo' => ['nullable', 'string', 'max:255'],
        ];
    }
}