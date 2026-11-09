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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MovieController extends Controller
{
    public function __construct(private MediaOptimizer $media) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');
        $genre = (string) $request->query('genre', '');
        $year = (int) $request->query('year', 0);
        $language = (string) $request->query('language', '');
        $tag = Movie::normalizeTag((string) $request->query('tag', ''));

        // Only accept values we actually offer (the navbar links), ignore anything else.
        $type = array_key_exists($type, config('auracinema.types')) ? $type : '';
        $genre = in_array($genre, config('auracinema.genres'), true) ? $genre : '';
        $language = in_array($language, config('auracinema.languages'), true) ? $language : '';
        $year = ($year >= 1900 && $year <= 2100) ? $year : 0;

        if ($q !== '' || $type !== '' || $genre !== '' || $year !== 0 || $language !== '' || $tag !== '') {
            $query = Movie::query()->withCount('episodes');

            if ($q !== '') {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('tags', 'like', $like));
            }
            if ($type !== '') {
                $query->where('type', $type);
            }
            if ($genre !== '') {
                $query->where('genre', $genre);
            }
            if ($language !== '') {
                // A movie counts for "Sinhala" if its Language is Sinhala OR it carries a "sinhala" keyword.
                $langTag = '%,'.addcslashes(Movie::normalizeTag($language), '%_\\').',%';
                $query->where(fn ($w) => $w->where('language', $language)->orWhere('tags', 'like', $langTag));
            }
            if ($year !== 0) {
                $query->where('year', $year);
            }
            if ($tag !== '') {
                $query->where('tags', 'like', '%,'.addcslashes($tag, '%_\\').',%');
            }

            // e.g. "Hindi · Romance · 2026 · Movies" - every active filter is named.
            $heading = $q !== ''
                ? 'Results for “'.$q.'”'
                : collect([
                    $language,
                    $genre,
                    $year ?: null,
                    $tag !== '' ? '#'.$tag : null,
                    $type !== '' ? match ($type) {
                        'movie' => 'Movies',
                        'series' => 'Drama / Series',
                        default => 'Anime',
                    } : null,
                ])->filter()->implode(' · ');

            return view('movies.browse', [
                'heading' => $heading,
                'results' => $query->latest()->simplePaginate(24)->withQueryString(),
            ]);
        }

        // One query for the rows (cheap on a catalogue this size), one paginated query for the grid.
        $recent = Movie::withCount('episodes')->latest()->take(40)->get();

        return view('movies.index', [
            'heroes' => $recent->filter(fn ($m) => $m->image)->take(5)->values(),
            'latest' => $recent->take(20),
            'withTrailers' => $recent->filter(fn ($m) => $m->trailer_url)->take(20),
            'fullMovies' => $recent->filter(fn ($m) => $m->stream_url)->take(20),
            'all' => Movie::withCount('episodes')->latest()->simplePaginate(24),
        ]);
    }

    /** "Suggestion" in the navbar: a fresh random pick every visit. */
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

        if ($request->hasFile('image')) {
            $data['image'] = $this->media->storePoster($request->file('image'));
        }
        if ($request->hasFile('trailer')) {
            $data['trailer_url'] = $request->file('trailer')->store('trailers', 'public');
        }

        $movie = DB::transaction(function () use ($data, $validated) {
            $movie = Movie::create($data);
            $this->syncEpisodes($movie, $validated['episodes'] ?? []);

            return $movie;
        });

        if ($request->hasFile('trailer')) {
            OptimizeTrailer::dispatchAfterResponse($movie->id, $data['trailer_url']);
        }

        return redirect()->route('movies.show', $movie)->with('success', 'Movie added. The trailer is being compressed in the background.');
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
        $disk = Storage::disk('public');

        if ($request->hasFile('image')) {
            $data['image'] = $this->media->storePoster($request->file('image'));
            if ($movie->image) {
                $disk->delete($movie->image);
            }
        }

        $newTrailer = null;
        if ($request->hasFile('trailer')) {
            $newTrailer = $request->file('trailer')->store('trailers', 'public');
            $data['trailer_url'] = $newTrailer;
            if ($movie->trailer_url) {
                $disk->delete($movie->trailer_url);
            }
        }

        DB::transaction(function () use ($movie, $data, $validated) {
            $movie->update($data);
            $this->syncEpisodes($movie, $validated['episodes'] ?? []);
        });

        if ($newTrailer) {
            OptimizeTrailer::dispatchAfterResponse($movie->id, $newTrailer);
        }

        return redirect()->route('movies.show', $movie)->with('success', 'Movie updated.');
    }

    public function destroy(Movie $movie)
    {
        $disk = Storage::disk('public');
        foreach ([$movie->image, $movie->trailer_url] as $path) {
            if ($path) {
                $disk->delete($path);
            }
        }
        $movie->delete();

        return redirect()->route('movies.index')->with('success', 'Movie deleted.');
    }

    /**
     * Validate the form. Episode rows the admin left empty are dropped first, and episodes are only
     * kept when the "has episodes" box is ticked.
     */
    private function validated(Request $request): array
    {
        $hasEpisodes = $request->boolean('has_episodes');

        $rows = $hasEpisodes
            ? collect($request->input('episodes', []))
                ->filter(fn ($row) => is_array($row) && filled($row['url'] ?? null))
                ->values()
                ->all()
            : [];
        $request->merge(['episodes' => $rows]);

        // "Watch now": accept a link or a pasted <iframe> snippet. Normalise it to a safe player URL,
        // or leave it as-is (non-empty) so the validation rule below rejects it with a clear message.
        $embed = trim((string) $request->input('embed_url', ''));
        if ($embed !== '') {
            $request->merge(['embed_url' => EmbedUrl::normalize($embed) ?? $embed]);
        }
        
        // Clean download links (normalize streamtape embed URL to video URL if necessary)
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

    /** Columns of the movies table from validated input. */
    private function movieData(array $validated, ?Movie $movie = null): array
    {
        $data = Arr::only($validated, [
            'title', 'description', 'stream_url', 'embed_url', 'genre', 'year', 'language',
            'download_480p', 'download_720p', 'download_1080p'
        ]);
        
        $data['tags'] = Movie::normalizeTags($validated['tags'] ?? null);
        $data['type'] = $validated['type'] ?? $movie?->type ?? 'movie';

        // A series uses per-episode links; a single "full movie" link would just be confusing next to them.
        if (! empty($validated['episodes'])) {
            $data['stream_url'] = null;
        }

        return $data;
    }

    /** Replace the episode list (small lists; one delete + one bulk insert). */
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
            // Posters are resized to 480px WebP on save, so the original only needs to be a sane size.
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            // Same 30MB cap on create AND update (they used to differ: 30MB vs 100MB).
            'trailer' => ['nullable', 'mimes:mp4,mkv,webm,mov', 'max:30720'],
            // http/https only: blocks javascript:, data:, ftp: links in the "Watch Full Movie" button.
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
            'episodes' => ['nullable', 'array', 'max:500'],
            'episodes.*.number' => ['required', 'integer', 'between:1,9999'],
            'episodes.*.url' => ['required', 'url:http,https', 'max:500'],
            
            // Download Links Validation
            'download_480p' => ['nullable', 'url:http,https', 'max:500'],
            'download_720p' => ['nullable', 'url:http,https', 'max:500'],
            'download_1080p' => ['nullable', 'url:http,https', 'max:500'],
        ];
    }
}