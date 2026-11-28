<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WatchHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebWatchHistoryController extends Controller
{
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['status' => 'guest']);
        }

        $data = $request->validate([
            'movie_id'         => 'required|integer',
            'episode_id'       => 'nullable|integer',
            'title'            => 'required|string|max:255',
            'poster_url'       => 'nullable|string',
            'type'             => 'nullable|string|max:50',
            'position_seconds' => 'nullable|integer|min:0',
            'duration_seconds' => 'nullable|integer|min:0',
        ]);

        $history = WatchHistory::updateOrCreate(
            [
                'user_id'    => Auth::id(),
                'movie_id'   => $data['movie_id'],
                'episode_id' => $data['episode_id'] ?? 0,
            ],
            [
                'title'            => $data['title'],
                'poster_url'       => $data['poster_url'] ?? null,
                'type'             => $data['type'] ?? 'movie',
                'position_seconds' => $data['position_seconds'] ?? 0,
                'duration_seconds' => $data['duration_seconds'] ?? 0,
            ]
        );

        $history->touch();

        return response()->json(['status' => 'saved', 'data' => $history]);
    }

    public function destroy(Request $request, $id)
    {
        WatchHistory::where('user_id', Auth::id())
            ->where('id', $id)
            ->delete();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'removed']);
        }

        return back();
    }
}