@extends('layouts.app')

@section('title', $movie->title)

@section('content')
    <nav class="mb-5 text-xs text-slate-400" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-white">Home</a> <span class="mx-1">/</span>
        <span class="text-slate-200">{{ $movie->title }}</span>
    </nav>

    <div class="mb-10 flex flex-col gap-6 md:flex-row md:gap-10">
        <div class="aspect-[2/3] w-44 shrink-0 self-center overflow-hidden rounded-lg bg-zinc-900 ring-1 ring-white/10 md:self-start">
            @if($movie->image)
                <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" width="480" height="720" fetchpriority="high" decoding="async" class="h-full w-full object-cover">
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <h1 class="mb-2 text-3xl font-extrabold tracking-tight">{{ $movie->title }}</h1>
            <p class="mb-5 text-xs text-slate-400">Added {{ $movie->created_at->format('d M Y') }}</p>
            <p class="mb-6 max-w-3xl whitespace-pre-line text-sm leading-relaxed text-slate-300">{{ $movie->description ?: 'No synopsis provided.' }}</p>

            @if($movie->stream_url)
                <a href="{{ $movie->stream_url }}" target="_blank" rel="noopener noreferrer nofollow"
                   class="inline-flex items-center gap-2 rounded-md bg-sky-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-sky-400">
                    <i class="fa-brands fa-telegram"></i> Watch full movie
                </a>
            @endif
            <a href="{{ route('movies.edit', $movie) }}" class="ml-2 text-xs text-slate-500 hover:text-slate-300">Manage</a>
        </div>
    </div>

    <section>
        <h2 class="mb-3 text-base font-bold">Trailer</h2>
        @if($movie->trailer_url)
            <div data-player class="relative mx-auto aspect-video w-full max-w-4xl overflow-hidden rounded-lg bg-black ring-1 ring-white/10">
                {{-- No src and preload="none": nothing is downloaded until the user presses play. --}}
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
@endsection
