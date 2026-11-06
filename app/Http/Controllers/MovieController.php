<?php

namespace App\Http\Controllers;

use App\Jobs\OptimizeTrailer;
use App\Models\Movie;
use App\Services\MediaOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class MovieController extends Controller
{
    public function __construct(private MediaOptimizer $media) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $results = Movie::where('title', 'like', $like)->latest()->simplePaginate(24)->withQueryString();

            return view('movies.index', ['q' => $q, 'results' => $results]);
        }

        // One query for the rows (cheap on a catalogue this size), one paginated query for the grid.
        $recent = Movie::latest()->take(40)->get();

        return view('movies.index', [
            'q' => '',
            'hero' => $recent->firstWhere(fn ($m) => $m->image),
            'latest' => $recent->take(20),
            'withTrailers' => $recent->filter(fn ($m) => $m->trailer_url)->take(20),
            'fullMovies' => $recent->filter(fn ($m) => $m->stream_url)->take(20),
            'all' => Movie::latest()->simplePaginate(24),
        ]);
    }

    public function create()
    {
        return view('movies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $data = Arr::only($validated, ['title', 'description', 'stream_url']);

        if ($request->hasFile('image')) {
            $data['image'] = $this->media->storePoster($request->file('image'));
        }
        if ($request->hasFile('trailer')) {
            $data['trailer_url'] = $request->file('trailer')->store('trailers', 'public');
        }

        $movie = Movie::create($data);

        if ($request->hasFile('trailer')) {
            OptimizeTrailer::dispatchAfterResponse($movie->id, $data['trailer_url']);
        }

        return redirect()->route('movies.show', $movie)->with('success', 'Movie added. The trailer is being compressed in the background.');
    }

    public function show(Movie $movie)
    {
        return view('movies.show', compact('movie'));
    }

    public function edit(Movie $movie)
    {
        return view('movies.edit', compact('movie'));
    }

    public function update(Request $request, Movie $movie)
    {
        $validated = $request->validate($this->rules());
        $data = Arr::only($validated, ['title', 'description', 'stream_url']);
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

        $movie->update($data);

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
        ];
    }
}
