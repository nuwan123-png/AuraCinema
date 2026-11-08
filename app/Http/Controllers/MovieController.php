<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Support\EmbedUrl;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $query = Movie::query();
        $isSearching = $request->filled('q') || $request->filled('genre');

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->input('q') . '%');
        }

        if ($request->filled('genre')) {
            $query->where('genre', $request->input('genre'));
        }

        $movies = $query->latest()->paginate(12)->withQueryString();
        $all    = $movies;

        // සෙවීමක් සිදු වන විට Interstellar වැනි අනෙකුත් චිත්‍රපට පිටුවේ පෙන්වීම වැළැක්වීමට හිස් collection ලබා දීම
        $heroes       = $isSearching ? collect() : Movie::inRandomOrder()->take(5)->get();
        $latest       = $isSearching ? collect() : Movie::latest()->take(10)->get();
        $withTrailers = $isSearching ? collect() : Movie::whereNotNull('trailer_path')->latest()->take(10)->get();
        $fullMovies   = $isSearching ? collect() : Movie::where(function ($q) {
            $q->whereNotNull('stream_url')->orWhereNotNull('embed_url');
        })->latest()->take(10)->get();

        return view('movies.index', compact(
            'movies',
            'all',
            'heroes',
            'latest',
            'withTrailers',
            'fullMovies'
        ));
    }

    public function create()
    {
        $movie = new Movie();
        return view('movies.create', compact('movie'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateMovie($request);

        if (!empty($validated['embed_url'])) {
            $normalized = EmbedUrl::normalize($validated['embed_url']);
            if (!$normalized) {
                return back()->withInput()->withErrors([
                    'embed_url' => 'Please provide a valid YouTube, Vimeo, Bunny, Cloudflare, or Streamtape link/embed code.',
                ]);
            }
            $validated['embed_url'] = $normalized;
        }

        if (!empty($validated['stream_url'])) {
            $validated['stream_url'] = trim($validated['stream_url']);
        }

        Movie::create($validated);

        return redirect()->route('movies.index')->with('success', 'Movie created successfully.');
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
        $validated = $this->validateMovie($request, $movie->id);

        if (!empty($validated['embed_url'])) {
            $normalized = EmbedUrl::normalize($validated['embed_url']);
            if (!$normalized) {
                return back()->withInput()->withErrors([
                    'embed_url' => 'Please provide a valid YouTube, Vimeo, Bunny, Cloudflare, or Streamtape link/embed code.',
                ]);
            }
            $validated['embed_url'] = $normalized;
        } else {
            $validated['embed_url'] = null;
        }

        if (array_key_exists('stream_url', $validated)) {
            $validated['stream_url'] = $validated['stream_url'] ? trim($validated['stream_url']) : null;
        }

        $movie->update($validated);

        return redirect()->route('movies.show', $movie)->with('success', 'Movie updated successfully.');
    }

    public function destroy(Movie $movie)
    {
        $movie->delete();

        return redirect()->route('movies.index')->with('success', 'Movie deleted successfully.');
    }

    protected function validateMovie(Request $request, $id = null): array
    {
        return $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'release_year'     => 'nullable|integer|min:1900|max:' . (date('Y') + 5),
            'duration_minutes' => 'nullable|integer|min:1',
            'poster_path'      => 'nullable|string|max:255',
            'trailer_path'     => 'nullable|string|max:255',
            'stream_url'       => 'nullable|url:http,https',
            'embed_url'        => 'nullable|string',
        ]);
    }
}