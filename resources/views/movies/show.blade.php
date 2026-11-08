@extends('layouts.app')

@section('title', $movie->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags($movie->description ?: 'Watch the trailer and details for '.$movie->title.' on AuraCinema.'), 155))
@section('og_type', 'video.movie')
@if($movie->image)
    @section('og_image', rtrim((string) config('auracinema.site_url'), '/').'/storage/'.$movie->image)
@endif
@push('jsonld')
    <script type="application/ld+json">{!! $movie->json_ld !!}</script>
@endpush

@section('content')
    <nav class="mb-5 text-xs text-slate-400" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-white">Home</a> <span class="mx-1">/</span>
        <span class="text-slate-200">{{ $movie->title }}</span>
    </nav>

    <div class="mb-10 flex flex-col gap-6 md:flex-row md:gap-10">
        <div class="aspect-[2/3] w-44 shrink-0 self-center overflow-hidden rounded-lg bg-zinc-900 ring-1 ring-white/10 md:self-start">
            @if($movie->image)
                <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" width="480" height="720" fetchpriority="high" decoding="async" class="h-full w-full object-cover">
            @else
                <div class="flex h-full w-full items-center justify-center px-3 text-center text-sm text-slate-500">No poster</div>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <h1 class="mb-2 text-3xl font-extrabold tracking-tight">{{ $movie->title }}</h1>
            <p class="mb-2 flex flex-wrap items-center gap-2 text-xs">
                <a href="{{ route('movies.index', ['type' => $movie->type]) }}" class="rounded bg-white/10 px-2 py-1 font-semibold text-slate-200 hover:bg-white/20">{{ $movie->type_label }}</a>
                @if($movie->genre)
                    <a href="{{ route('movies.index', ['genre' => $movie->genre]) }}" class="rounded bg-white/10 px-2 py-1 text-slate-200 hover:bg-white/20">{{ $movie->genre }}</a>
                @endif
                @if($movie->year)
                    <a href="{{ route('movies.index', ['year' => $movie->year]) }}" class="rounded bg-white/10 px-2 py-1 text-slate-200 hover:bg-white/20">{{ $movie->year }}</a>
                @endif
                @if($movie->episodes->isNotEmpty())
                    <span class="rounded bg-orange-500/15 px-2 py-1 font-semibold text-orange-300">{{ $movie->episodes->count() }} EPS</span>
                @endif
            </p>
            <p class="mb-5 text-xs text-slate-400">Added {{ $movie->created_at->format('d M Y') }}</p>
            <p class="mb-6 max-w-3xl whitespace-pre-line text-sm leading-relaxed text-slate-300">{{ $movie->description ?: 'No synopsis provided.' }}</p>

            @if($movie->stream_url)
                <a href="{{ $movie->stream_url }}" target="_blank" rel="noopener noreferrer nofollow"
                   class="inline-flex items-center gap-2 rounded-md bg-sky-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-sky-400">
                    <i class="fa-brands fa-telegram"></i> Watch full movie
                </a>
            @endif
            @if(request()->cookie('aura_admin'))
                <a href="{{ route('movies.edit', $movie) }}" class="ml-2 inline-flex items-center gap-2 rounded-md bg-orange-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-orange-600">
                    <i class="fa-solid fa-pen-to-square"></i> Edit
                </a>
            @endif
        </div>
    </div>

    @if($movie->episodes->isNotEmpty())
        <section class="mb-10" id="episodes">
            <h2 class="mb-3 border-l-4 border-orange-500 pl-3 text-base font-bold uppercase tracking-wide text-orange-400">Episodes</h2>
            <div class="grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10">
                @foreach($movie->episodes as $episode)
                    <a href="{{ $episode->url }}" target="_blank" rel="noopener noreferrer nofollow"
                       aria-label="Watch episode {{ $episode->number }}"
                       class="flex items-center justify-center gap-1.5 rounded-md bg-white/5 py-3 text-sm font-semibold text-slate-200 ring-1 ring-white/5 transition hover:bg-sky-500 hover:text-white">
                        <i class="fa-brands fa-telegram text-xs text-sky-400"></i>{{ $episode->number }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section>
        <h2 class="mb-3 text-base font-bold">Trailer</h2>
        @if($movie->trailer_url)
            <div data-player class="relative mx-auto aspect-video w-full max-w-4xl overflow-hidden rounded-lg bg-black ring-1 ring-white/10">
                <video data-src="{{ $movie->trailer_file_url }}" preload="none" playsinline
                       @if($movie->image) poster="{{ $movie->poster_url }}" @endif
                       class="h-full w-full bg-black object-contain"></video>
                <button type="button" data-play class="absolute inset-0 grid place-items-center bg-black/40 transition hover:bg-black/30" aria-label="Play trailer">
                    <span class="flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-bold text-black">
                        <i class="fa-solid fa-play"></i><span data-play-label>Play</span>
                    </span>
                </button>
            </div>
        @else
            <p class="rounded-lg border border-white/5 bg-white/[0.03] py-12 text-center text-sm text-slate-500">No trailer uploaded for this title.</p>
        @endif
    </section>

    @if($movie->embed_url)
        <section class="mt-10" id="watch">
            <h2 class="mb-3 text-base font-bold">Watch now</h2>
            <div data-embed class="mx-auto w-full max-w-4xl">
                <button type="button" data-embed-load data-embed-src="{{ $movie->embed_url }}" data-embed-title="{{ $movie->title }}"
                        class="grid aspect-video w-full place-items-center rounded-lg bg-black ring-1 ring-white/10 transition hover:ring-orange-400/60"
                        aria-label="Load the player for {{ $movie->title }}"[cite: 13]>
                    <span class="flex items-center gap-2 rounded-full bg-orange-500 px-7 py-3 text-sm font-bold text-white">
                        <i class="fa-solid fa-play"></i> Watch now
                    </span>
                </button>
            </div>
        </section>
    @endif
@endsection