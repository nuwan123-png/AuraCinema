@extends('layouts.app')

@section('title', 'Incomplete titles')
@section('robots', 'noindex,nofollow')

@section('content')
    <h1 class="mb-1 text-lg font-bold">Incomplete titles
        <span class="text-sm font-normal text-slate-400">({{ $movies->count() }} of {{ $total }})</span>
    </h1>
    <p class="mb-5 text-xs text-slate-500">Titles whose form still has empty fields. Click Edit, fill them in and press Update.</p>

    @forelse($movies as $movie)
        <div class="mb-2 flex items-center gap-3 rounded-md border border-white/10 bg-zinc-900/60 p-3">
            <div class="h-16 w-11 shrink-0 overflow-hidden rounded bg-zinc-800 ring-1 ring-white/10">
                @if($movie->image)
                    <img src="{{ $movie->poster_url }}" alt="" width="44" height="64" loading="lazy" class="h-full w-full object-cover">
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <a href="{{ route('movies.show', $movie) }}" class="block truncate text-sm font-semibold text-white hover:text-orange-300">{{ $movie->title }}</a>
                <div class="mt-1.5 flex flex-wrap gap-1">
                    @foreach($movie->missingFields() as $field)
                        <span class="rounded-full bg-rose-500/15 px-2 py-0.5 text-[10px] font-semibold text-rose-300 ring-1 ring-rose-500/30">{{ $field }}</span>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('movies.edit', $movie) }}" class="btn-aura shrink-0 !px-4 !py-2 text-xs">
                <i class="fa-solid fa-pen-to-square"></i> Edit
            </a>
        </div>
    @empty
        <div class="rounded-md border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-300">
            <i class="fa-solid fa-circle-check"></i> All titles are complete.
        </div>
    @endforelse
@endsection
