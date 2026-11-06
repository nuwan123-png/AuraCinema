@extends('layouts.app')

@section('content')

<div class="max-w-xl mx-auto glass-card rounded-3xl p-8 border border-white/10 shadow-2xl my-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Edit Movie Details</h2>
            <p class="text-xs text-slate-400">Title, Poster, Description හෝ Video Trailer එක වෙනස් කරන්න.</p>
        </div>
        <a href="{{ route('movies.show', $movie->id) }}" class="text-xs text-slate-400 hover:text-white transition">Cancel</a>
    </div>

    <form action="{{ route('movies.update', $movie->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')

        <!-- Title -->
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Movie Title</label>
            <input type="text" name="title" value="{{ old('title', $movie->title) }}" required class="w-full bg-black/30 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-400/60 transition">
        </div>

        <!-- Description -->
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Description / Synopsis</label>
            <textarea name="description" rows="3" class="w-full bg-black/30 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-400/60 transition">{{ old('description', $movie->description) }}</textarea>
        </div>

        <!-- Poster Image Update -->
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Poster Cover (Image)</label>
            @if($movie->image)
                <div class="flex items-center gap-3 mb-2 p-2 rounded-xl bg-black/20 border border-white/5">
                    <img src="{{ asset('storage/' . $movie->image) }}" class="w-10 h-14 object-cover rounded-lg border border-white/10">
                    <span class="text-[11px] text-slate-400">දැනට පවතින Poster එක. අලුත් එකක් දැමීමට පහතින් තෝරන්න.</span>
                </div>
            @endif
            <input type="file" name="image" accept="image/*" class="w-full bg-black/30 border border-white/10 rounded-xl p-2 text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-cyan-500/20 file:text-cyan-300 file:text-xs file:font-semibold hover:file:bg-cyan-500/30 cursor-pointer">
        </div>

        <!-- Trailer Video Update -->
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Trailer File (MP4, Up to 100MB)</label>
            @if($movie->trailer_url)
                <div class="mb-2 p-2 rounded-xl bg-black/20 border border-white/5 text-[11px] text-cyan-400 flex items-center gap-2">
                    <i class="fa fa-film"></i>
                    <span>දැනට වීඩියෝවක් ඇත. වෙනස් කිරීමට අවශ්‍ය නම් පමණක් අලුත් වීඩියෝවක් තෝරන්න.</span>
                </div>
            @endif
            <input type="file" name="trailer" accept="video/mp4,video/mkv" class="w-full bg-black/30 border border-white/10 rounded-xl p-2 text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-orange-500/20 file:text-orange-300 file:text-xs file:font-semibold hover:file:bg-orange-500/30 cursor-pointer">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                <i class="fa-brands fa-telegram text-sky-400 mr-1"></i> Telegram Movie Stream Link
            </label>
            <input type="url" name="stream_url" value="{{ old('stream_url', $movie->stream_url ?? '') }}" placeholder="https://t.me/..." class="w-full bg-black/30 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-400/60 transition">
        </div>

        <!-- Submit Button -->
        <div class="pt-4 flex items-center justify-between">
            <a href="{{ route('movies.show', $movie->id) }}" class="text-xs text-slate-400 hover:text-white transition">Back</a>
            <button type="submit" class="bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-slate-950 text-xs font-bold px-6 py-2.5 rounded-xl shadow-lg shadow-cyan-500/25 transition transform hover:-translate-y-0.5">
                Update Movie
            </button>
        </div>
    </form>
</div>

@endsection