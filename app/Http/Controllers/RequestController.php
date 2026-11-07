<?php

namespace App\Http\Controllers;

use App\Models\MovieRequest;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    /** Public: "Request" in the navbar. */
    public function create()
    {
        return view('requests.create');
    }

    /** Public, rate limited in routes/web.php. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'max:0'], // honeypot: real visitors never see or fill this
        ]);

        // Hard cap so a bot can never fill the database; the admin clears old requests.
        if (MovieRequest::count() >= 1000) {
            return back()->withInput()->withErrors(['title' => 'Requests are full right now. Please try again later.']);
        }

        MovieRequest::create(['title' => $data['title'], 'note' => $data['note'] ?? null]);

        return redirect()->route('home')->with('success', 'Thanks! Your request was received.');
    }

    /** Admin only: see what people asked for. */
    public function index()
    {
        return view('requests.index', ['requests' => MovieRequest::latest()->limit(200)->get()]);
    }

    public function destroy(MovieRequest $movieRequest)
    {
        $movieRequest->delete();

        return back()->with('success', 'Request removed.');
    }
}
