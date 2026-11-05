@extends('layouts.app')

@section('content')

    <!-- Breadcrumb -->
    <div class="mb-6 flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('movies.index') }}" class="hover:text-white transition">Collection</a>
        <span>/</span>
        <span class="text-cyan-400 font-medium">{{ $movie->title }}</span>
    </div>

    <!-- Metadata Glass Showcase -->
    <div class="glass-card rounded-3xl p-6 lg:p-8 mb-8 border border-white/10 flex flex-col md:flex-row gap-8 items-start shadow-2xl">
        <div class="w-48 aspect-[2/3] rounded-2xl overflow-hidden bg-black/50 border border-white/10 flex-shrink-0 shadow-lg">
            @if($movie->image)
                <img src="{{ asset('storage/' . $movie->image) }}" alt="{{ $movie->title }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center text-xs text-slate-500">No Image</div>
            @endif
        </div>

        <div class="flex-1 flex flex-col justify-between h-full w-full">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-full bg-cyan-500/10 text-cyan-300 border border-cyan-500/20 text-[10px] font-bold uppercase tracking-wider">
                            Verified Source
                        </span>
                        <span class="px-2.5 py-1 rounded-full bg-orange-500/10 text-orange-400 border border-orange-500/20 text-[10px] font-bold">
                            Full HD
                        </span>
                    </div>

                    <form action="{{ route('movies.destroy', $movie->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this title?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/20 text-xs transition">
                            <i class="fa fa-trash-can text-[11px]"></i>
                            <span>Remove</span>
                        </button>
                    </form>
                </div>

                <h1 class="text-3xl font-extrabold text-white mb-4 tracking-tight">{{ $movie->title }}</h1>

                <div class="glass-panel p-4 rounded-2xl mb-6">
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Synopsis / Storyline</h3>
                    <p class="text-xs text-slate-300 leading-relaxed font-light">
                        {{ $movie->description ?? 'No synopsis provided.' }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                <div class="glass-panel p-3 rounded-xl border border-white/5">
                    <span class="text-slate-400 block text-[10px]">Uploaded On</span>
                    <span class="text-white font-medium">{{ $movie->created_at->format('d M, Y') }}</span>
                </div>
                <div class="glass-panel p-3 rounded-xl border border-white/5">
                    <span class="text-slate-400 block text-[10px]">Format Support</span>
                    <span class="text-white font-medium">MP4 / H.264</span>
                </div>
                <div class="glass-panel p-3 rounded-xl border border-white/5">
                    <span class="text-slate-400 block text-[10px]">Playback</span>
                    <span class="text-emerald-400 font-medium">Direct Stream</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Trailer Player -->
    <div class="glass-card rounded-3xl p-6 lg:p-8 border border-white/10 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-white flex items-center gap-2">
                <i class="fa fa-circle-play text-orange-400"></i>
                <span>Trailer Player</span>
            </h2>
            <span class="text-xs text-slate-400">Direct Stream</span>
        </div>

        @if($movie->trailer_url)
            <div class="w-full aspect-video rounded-2xl overflow-hidden bg-black border border-white/10 shadow-2xl max-w-4xl mx-auto relative">
                <video controls playsinline preload="auto" class="w-full h-full block">
                    <source src="{{ asset('storage/' . $movie->trailer_url) }}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        @else
            <div class="aspect-[16/6] bg-black/30 rounded-2xl flex flex-col items-center justify-center border border-white/5 text-slate-500">
                <i class="fa fa-video-slash text-2xl mb-2 text-slate-600"></i>
                <p class="text-xs">No video trailer uploaded for this movie.</p>
            </div>
        @endif
    </div>

@endsection