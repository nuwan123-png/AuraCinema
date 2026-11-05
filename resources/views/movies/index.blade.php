@extends('layouts.app')

@section('content')

    <!-- Featured Hero Glass Card -->
    @if(isset($featured) && $featured)
    <div class="relative glass-card rounded-3xl overflow-hidden mb-12 border border-white/10 p-8 flex flex-col md:flex-row items-center gap-8 shadow-2xl">
        <div class="w-full md:w-1/3 aspect-[16/10] rounded-2xl overflow-hidden relative shadow-lg border border-white/10">
            @if($featured->image)
                <img src="{{ asset('storage/' . $featured->image) }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full bg-slate-800/60 flex items-center justify-center text-slate-500 text-xs">No Cover Image</div>
            @endif
            <span class="absolute top-3 right-3 px-2 py-1 bg-black/60 backdrop-blur-md rounded-lg text-[10px] text-cyan-300 font-bold border border-cyan-400/30">
                Featured
            </span>
        </div>

        <div class="w-full md:w-2/3 flex flex-col justify-center">
            <div class="flex items-center gap-2 mb-3">
                <span class="px-2.5 py-1 rounded-full bg-orange-500/10 text-orange-400 border border-orange-500/20 text-[11px] font-semibold">
                    Editor's Pick
                </span>
                <span class="text-xs text-slate-400">• {{ $featured->created_at->format('M Y') }}</span>
            </div>

            <h1 class="text-3xl lg:text-4xl font-extrabold text-white mb-3 tracking-tight">{{ $featured->title }}</h1>
            <p class="text-xs text-slate-300 leading-relaxed line-clamp-3 mb-6 max-w-2xl font-light">
                {{ $featured->description ?? 'No synopsis provided for this title.' }}
            </p>

            <div class="flex items-center gap-4">
                <a href="{{ route('movies.show', $featured->id) }}" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-cyan-500 hover:bg-cyan-600 text-slate-950 font-bold text-xs shadow-lg shadow-cyan-500/20 transition transform hover:-translate-y-0.5">
                    <i class="fa fa-play text-[10px]"></i>
                    <span>Watch Trailer & Details</span>
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Catalog Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-bold text-white tracking-tight">Vault Collection</h2>
            <p class="text-xs text-slate-400">{{ count($movies) }} Title(s) Registered</p>
        </div>

        <div class="flex items-center gap-2 bg-white/5 p-1 rounded-xl border border-white/10 text-xs">
            <span class="px-3 py-1 rounded-lg bg-white/10 text-white font-medium">All Titles</span>
            <span class="px-3 py-1 rounded-lg text-slate-400 hover:text-white cursor-pointer transition">Recent</span>
        </div>
    </div>

    <!-- Movies Glass Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
        @forelse($movies as $movie)
            <div class="glass-card rounded-2xl overflow-hidden p-3 flex flex-col justify-between group transition-all duration-300 hover:-translate-y-1">
                <div>
                    <a href="{{ route('movies.show', $movie->id) }}" class="block relative aspect-[2/3] rounded-xl overflow-hidden bg-black/40 mb-3 border border-white/5">
                        @if($movie->image)
                            <img src="{{ asset('storage/' . $movie->image) }}" alt="{{ $movie->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-xs text-slate-500">No Image</div>
                        @endif
                        <span class="absolute top-2 right-2 bg-slate-950/70 backdrop-blur-md text-[9px] text-cyan-300 px-2 py-0.5 rounded-md font-semibold border border-white/10">
                            HD
                        </span>
                    </a>

                    <h3 class="text-xs font-semibold text-white line-clamp-1 group-hover:text-cyan-400 transition">
                        <a href="{{ route('movies.show', $movie->id) }}">{{ $movie->title }}</a>
                    </h3>
                    <p class="text-[11px] text-slate-400 line-clamp-1 mt-0.5">{{ $movie->description ?? 'No description' }}</p>
                </div>

                <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-[11px]">
                    <a href="{{ route('movies.show', $movie->id) }}" class="text-cyan-400 hover:text-cyan-300 font-medium">
                        View <i class="fa fa-arrow-right text-[9px]"></i>
                    </a>

                    <form action="{{ route('movies.destroy', $movie->id) }}" method="POST" onsubmit="return confirm('Do you want to delete this title?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-rose-400/80 hover:text-rose-400 p-1 transition" title="Delete">
                            <i class="fa fa-trash-can text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full glass-card p-12 rounded-3xl text-center">
                <i class="fa fa-film text-3xl text-slate-600 mb-3"></i>
                <p class="text-sm text-slate-400 mb-4">No movies uploaded to your vault yet.</p>
                <a href="{{ route('movies.create') }}" class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold px-4 py-2 rounded-xl transition">
                    <i class="fa fa-plus text-[10px]"></i> Add First Movie
                </a>
            </div>
        @endforelse
    </div>

@endsection