<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Support\EmbedUrl;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Movie::query();

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->input('q') . '%');
        }

        $movies = $query->latest()->paginate(12)->withQueryString();

        // Testing suite එකේ බලාපොරොත්තු වන $heroes variable එක (featured movies හෝ empty collection එකක්)
        $heroes = Movie::inRandomOrder()->take(5)->get();

        return view('movies.index', compact('movies', 'heroes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $movie = new Movie();
        return view('movies.create', compact('movie'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateMovie($request);

        // Normalize embed_url if present
        if (!empty($validated['embed_url'])) {
            $normalized = EmbedUrl::normalize($validated['embed_url']);
            if (!$normalized) {
                return back()->withInput()->withErrors([
                    'embed_url' => 'Please provide a valid YouTube, Vimeo, Bunny, Cloudflare, or Streamtape link/embed code.',
                ]);
            }
            $validated['embed_url'] = $normalized;
        }

        // Project tests වල stream_url අගය ඇත්නම් එය ආරක්ෂා කර ගැනීම
        if (!empty($validated['stream_url'])) {
            $validated['stream_url'] = trim($validated['stream_url']);
        }

        Movie::create($validated);

        return redirect()->route('movies.index')->with('success', 'Movie created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Movie $movie)
    {
        return view('movies.show', compact('movie'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Movie $movie)
    {
        return view('movies.edit', compact('movie'));
    }

    /**
     * Update the specified resource in storage.
     */
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Movie $movie)
    {
        $movie->delete();

        return redirect()->route('movies.index')->with('success', 'Movie deleted successfully.');
    }

    /**
     * Validation rules supporting existing tests and the new embed field.
     */
    protected function validateMovie(Request $request, $id = null): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'release_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 5),
            'duration_minutes' => 'nullable|integer|min:1',
            'poster_path' => 'nullable|string|max:255',
            'trailer_path' => 'nullable|string|max:255',
            // ExampleTest එකේ test_stream_url_must_be_http_or_https සඳහා:
            'stream_url' => 'nullable|url:http,https',
            // අලුත් player එක සඳහා:
            'embed_url' => 'nullable|string',
        ]);
    }
}