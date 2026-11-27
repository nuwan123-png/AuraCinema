<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WatchHistory;
use Illuminate\Http\Request;

class WatchHistoryController extends Controller
{
    public function index(Request $request)
    {
        $history = WatchHistory::where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        return response()->json(['data' => $history]);
    }

    public function store(Request $request)
    {
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
                'user_id'    => $request->user()->id,
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

        return response()->json(['data' => $history]);
    }

    public function destroy(Request $request, $id)
    {
        WatchHistory::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->delete();

        return response()->json(['message' => 'Removed']);
    }
}