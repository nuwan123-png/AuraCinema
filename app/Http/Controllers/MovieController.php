<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MovieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $movies = Movie::latest()->get();
        $featured = $movies->first();

        return view('movies.index', compact('movies', 'featured'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('movies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'trailer' => 'nullable|mimes:mp4,mkv|max:102400',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('posters', 'public');
        }

        if ($request->hasFile('trailer')) {
            $validated['trailer_url'] = $request->file('trailer')->store('trailers', 'public');
        }

        Movie::create($validated);

        return redirect()->route('movies.index')->with('success', 'Movie added successfully!');
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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'trailer' => 'nullable|mimes:mp4,mkv|max:102400',
        ]);

        if ($request->hasFile('image')) {
            if ($movie->image && Storage::disk('public')->exists($movie->image)) {
                Storage::disk('public')->delete($movie->image);
            }
            $validated['image'] = $request->file('image')->store('posters', 'public');
        }

        if ($request->hasFile('trailer')) {
            if ($movie->trailer_url && Storage::disk('public')->exists($movie->trailer_url)) {
                Storage::disk('public')->delete($movie->trailer_url);
            }
            $validated['trailer_url'] = $request->file('trailer')->store('trailers', 'public');
        }

        $movie->update($validated);

        return redirect()->route('movies.show', $movie->id)->with('success', 'Movie successfully updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Movie $movie)
    {
        if ($movie->image && Storage::disk('public')->exists($movie->image)) {
            Storage::disk('public')->delete($movie->image);
        }

        if ($movie->trailer_url && Storage::disk('public')->exists($movie->trailer_url)) {
            Storage::disk('public')->delete($movie->trailer_url);
        }

        $movie->delete();

        return redirect()->route('movies.index')->with('success', 'Movie deleted successfully!');
    }
}