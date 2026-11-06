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

                    <!-- Action Buttons: Edit & Remove -->
                    <div class="flex items-center gap-2">
                        <a href="{{ route('movies.edit', $movie) }}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-300 border border-cyan-500/20 text-xs transition">
                            <i class="fa fa-pen-to-square text-[11px]"></i>
                            <span>Edit</span>
                        </a>

                        <form action="{{ route('movies.destroy', $movie) }}" method="POST" onsubmit="return confirm('මෙම චිත්‍රපටය ඉවත් කිරීමට අවශ්‍යද?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/20 text-xs transition">
                                <i class="fa fa-trash-can text-[11px]"></i>
                                <span>Remove</span>
                            </button>
                        </form>
                    </div>
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
                    <span class="text-white font-medium">MP4 / MKV</span>
                </div>
                <div class="glass-panel p-3 rounded-xl border border-white/5">
                    <span class="text-slate-400 block text-[10px]">Playback</span>
                    <span class="text-emerald-400 font-medium">Direct Stream</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Glass Video Trailer Player -->
    <div class="glass-card rounded-3xl p-6 lg:p-8 border border-white/10 shadow-2xl mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-white flex items-center gap-2">
                <i class="fa fa-circle-play text-orange-400"></i>
                <span>Trailer Player</span>
            </h2>
            <span class="text-xs text-slate-400">Preview</span>
        </div>

        @if($movie->trailer_url)
            <div class="w-full aspect-video rounded-2xl overflow-hidden bg-black border border-white/10 shadow-2xl max-w-4xl mx-auto relative z-10">
                <video controls playsinline preload="metadata" class="w-full h-full block">
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

        <!-- Telegram Full Movie Button (ට්‍රේලර් එකට පහළින් දිස්වන කොටස) -->
        @if($movie->stream_url)
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-between p-5 rounded-2xl glass-panel border border-sky-500/20 bg-sky-950/20">
                <div class="flex items-center gap-3 mb-3 sm:mb-0">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/20 border border-sky-400/30 flex items-center justify-center text-sky-400 text-xl flex-shrink-0">
                        <i class="fa-brands fa-telegram"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-white block">Full Movie Available</span>
                        <span class="text-[11px] text-slate-400">අපගේ Telegram නාලිකාව හරහා සම්පූර්ණ චිත්‍රපටය නරඹන්න</span>
                    </div>
                </div>

                <a href="{{ $movie->stream_url }}" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold text-xs shadow-lg shadow-sky-500/20 transition transform hover:-translate-y-0.5">
                    <span>Watch Full Movie</span>
                    <i class="fa fa-arrow-up-right-from-square text-[10px]"></i>
                </a>
            </div>
        @endif
    </div>

@endsection