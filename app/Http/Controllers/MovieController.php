<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Support\EmbedUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MovieController extends Controller
{
    public function index()
    {
        $movies = Movie::latest()->paginate(12);
        return view('movies.index', compact('movies'));
    }

    public function show(Movie $movie)
    {
        return view('movies.show', compact('movie'));
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

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        Movie::create($validated);

        return redirect()->route('movies.index')->with('success', 'Movie created successfully.');
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
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:movies,slug,' . $id,
            'description' => 'nullable|string',
            'release_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 5),
            'duration_minutes' => 'nullable|integer|min:1',
            'poster_path' => 'nullable|string|max:255',
            'trailer_path' => 'nullable|string|max:255',
            'embed_url' => 'nullable|string',
        ]);
    }
}
