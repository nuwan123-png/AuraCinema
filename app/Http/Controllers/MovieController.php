<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MovieController extends Controller
{
    public function index()
    {
        $movies = Movie::latest()->get();
        $featured = $movies->first();
        return view('movies.index', compact('movies', 'featured'));
    }

    public function create()
    {
        return view('movies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'nullable',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'trailer' => 'nullable|mimes:mp4,mov,mkv|max:51200',
        ]);

        $data = $request->only(['title', 'description']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('posters', 'public');
        }

        if ($request->hasFile('trailer')) {
            $data['trailer_url'] = $request->file('trailer')->store('trailers', 'public');
        }

        Movie::create($data);

        return redirect()->route('movies.index')->with('success', 'Movie uploaded successfully!');
    }

    public function show($id)
    {
        $movie = Movie::findOrFail($id);
        return view('movies.show', compact('movie'));
    }

    public function destroy($id)
    {
        $movie = Movie::findOrFail($id);

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