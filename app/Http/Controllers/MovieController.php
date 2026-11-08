<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Support\EmbedUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MovieController extends Controller
{
    /**
     * Views සහ Navbar layout එකට අවශ්‍ය shared helpers සහ data ලබා දීම
     */
    protected function getViewData(Request $request, array $extra = []): array
    {
        $navType = $request->input('type');
        $navLink = function (bool $active): string {
            return $active
                ? 'text-orange-400 font-semibold px-2 py-1'
                : 'text-zinc-300 hover:text-white px-2 py-1 transition';
        };

        return array_merge([
            'navType' => $navType,
            'navLink' => $navLink,
        ], $extra);
    }

    public function index(Request $request)
    {
        $query = Movie::query();
        $isSearching = $request->filled('q') || $request->filled('genre') || $request->filled('type');

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->input('q') . '%');
        }

        if ($request->filled('genre') && Schema::hasColumn('movies', 'genre')) {
            $query->where('genre', $request->input('genre'));
        }

        if ($request->filled('type') && Schema::hasColumn('movies', 'type')) {
            $query->where('type', $request->input('type'));
        }

        $movies = $query->latest()->paginate(12)->withQueryString();
        $all    = $movies;

        if ($isSearching) {
            $heroes       = collect();
            $latest       = collect();
            $withTrailers = collect();
            $fullMovies   = collect();
        } else {
            $heroes = Movie::inRandomOrder()->take(5)->get();
            $latest = Movie::latest()->take(10)->get();

            $withTrailers = Schema::hasColumn('movies', 'trailer_path')
                ? Movie::whereNotNull('trailer_path')->latest()->take(10)->get()
                : collect();

            $fullMovies = Movie::where(function ($q) {
                if (Schema::hasColumn('movies', 'stream_url')) {
                    $q->whereNotNull('stream_url');
                }
                if (Schema::hasColumn('movies', 'embed_url')) {
                    $q->orWhereNotNull('embed_url');
                }
            })->latest()->take(10)->get();
        }

        return view('movies.index', $this->getViewData($request, compact(
            'movies',
            'all',
            'heroes',
            'latest',
            'withTrailers',
            'fullMovies'
        )));
    }

    public function create(Request $request)
    {
        $movie = new Movie();
        return view('movies.create', $this->getViewData($request, compact('movie')));
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

        // Database එකේ සැබවින්ම පවතින columns වලට පමණක් insert කිරීම
        $dataToSave = [];
        foreach ($validated as $column => $value) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('movies', $column)) {
                $dataToSave[$column] = $value;
            }
        }

        Movie::create($dataToSave);

        return redirect()->route('movies.index')->with('success', 'Movie created successfully.');
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

        $dataToUpdate = [];
        foreach ($validated as $column => $value) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('movies', $column)) {
                $dataToUpdate[$column] = $value;
            }
        }

        $movie->update($dataToUpdate);

        return redirect()->route('movies.show', $movie)->with('success', 'Movie updated successfully.');
    }

    public function show(Request $request, Movie $movie)
    {
        return view('movies.show', $this->getViewData($request, compact('movie')));
    }

    public function edit(Request $request, Movie $movie)
    {
        return view('movies.edit', $this->getViewData($request, compact('movie')));
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