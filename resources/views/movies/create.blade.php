@extends('layouts.app')

@section('content')

<div class="max-w-xl mx-auto glass-card rounded-3xl p-8 border border-white/10 shadow-2xl my-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white tracking-tight">Upload New Title</h2>
        <p class="text-xs text-slate-400">Add metadata, cover image, and local video trailer.</p>
    </div>

    <form action="{{ route('movies.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Movie Title</label>
            <input type="text" name="title" required placeholder="e.g. Inception (2010)" class="w-full bg-black/30 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-400/60 transition">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Description / Synopsis</label>
            <textarea name="description" rows="3" placeholder="Enter storyline..." class="w-full bg-black/30 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-400/60 transition"></textarea>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Poster Cover (Image)</label>
            <input type="file" name="image" accept="image/*" class="w-full bg-black/30 border border-white/10 rounded-xl p-2 text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-cyan-500/20 file:text-cyan-300 file:text-xs file:font-semibold hover:file:bg-cyan-500/30 cursor-pointer">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Trailer File (MP4, Up to 100MB)</label>
            <input type="file" name="trailer" accept="video/mp4,video/mkv" class="w-full bg-black/30 border border-white/10 rounded-xl p-2 text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-orange-500/20 file:text-orange-300 file:text-xs file:font-semibold hover:file:bg-orange-500/30 cursor-pointer">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                <i class="fa-brands fa-telegram text-sky-400 mr-1"></i> Telegram Movie Stream Link
            </label>
            <input type="url" name="stream_url" value="{{ old('stream_url', $movie->stream_url ?? '') }}" placeholder="https://t.me/..." class="w-full bg-black/30 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-400/60 transition">
        </div>

        <div class="pt-4 flex items-center justify-between">
            <a href="{{ route('movies.index') }}" class="text-xs text-slate-400 hover:text-white transition">Cancel</a>
            <button type="submit" class="bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white text-xs font-bold px-6 py-2.5 rounded-xl shadow-lg shadow-orange-500/25 transition transform hover:-translate-y-0.5">
                Save & Upload
            </button>
        </div>
    </form>
</div>

@endsection