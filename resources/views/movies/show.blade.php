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
    @include('movies._incomplete_notice', ['movie' => $movie])

    <nav class="mb-5 text-xs text-slate-400" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-white">Home</a> <span class="mx-1">/</span>
        <span class="text-slate-200">{{ $movie->title }}</span>
    </nav>

    <div class="relative isolate mb-10 flex flex-col gap-6 overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] p-5 shadow-2xl shadow-black/40 sm:p-8 md:flex-row md:gap-10">
        @if($movie->image)
            <img src="{{ $movie->poster_url }}" alt="" aria-hidden="true" width="48" height="72" loading="lazy" decoding="async"
                 class="pointer-events-none absolute inset-0 -z-10 h-full w-full scale-125 object-cover opacity-20 blur-3xl">
        @endif
        <div class="aspect-[2/3] w-44 shrink-0 self-center overflow-hidden rounded-xl bg-zinc-900 shadow-2xl shadow-black/60 ring-1 ring-white/15 md:self-start">
            @if($movie->image)
                <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" width="480" height="720" fetchpriority="high" decoding="async" class="h-full w-full object-cover">
            @else
                <div class="flex h-full w-full items-center justify-center px-3 text-center text-sm text-slate-500">No poster</div>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <h1 class="mb-3 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $movie->title }}</h1>
            <p class="mb-2 flex flex-wrap items-center gap-2 text-xs">
                <a href="{{ route('movies.index', ['type' => $movie->type]) }}" class="chip">{{ $movie->type_label }}</a>
                @if($movie->genre)
                    <a href="{{ route('movies.index', ['genre' => $movie->genre]) }}" class="chip">{{ $movie->genre }}</a>
                @endif
                @if($movie->language)
                    <a href="{{ route('movies.index', ['language' => $movie->language]) }}" class="chip">{{ $movie->language }}</a>
                @endif
                @if($movie->year)
                    <a href="{{ route('movies.index', ['year' => $movie->year]) }}" class="chip">{{ $movie->year }}</a>
                @endif
                
                {{-- Tags සදහා දෝෂ රහිත නිවැරදි ලූපය --}}
                @php
                    $cleanTags = array_filter(array_map('trim', explode(',', (string) $movie->tags)));
                @endphp
                @foreach($cleanTags as $tag)
                    <a href="{{ route('movies.index', ['tag' => mb_strtolower($tag)]) }}" class="chip-tag">#{{ $tag }}</a>
                @endforeach

                @if($movie->episodes->isNotEmpty())
                    <span class="rounded-full bg-gradient-to-r from-orange-500 to-amber-500 px-3 py-1 text-xs font-bold text-white">{{ $movie->episodes->count() }} EPS</span>
                @endif
            </p>
            <p class="mb-5 text-xs text-slate-400">Added {{ $movie->created_at->format('d M Y') }}</p>
            <p class="mb-6 max-w-3xl whitespace-pre-line text-sm leading-relaxed text-slate-300">{{ $movie->description ?: 'No synopsis provided.' }}</p>

            <div class="flex flex-wrap items-center gap-3">
                @if($movie->trailer_url)
                    <button type="button" data-trailer-open class="btn-ghost" aria-haspopup="dialog">
                        <i class="fa-solid fa-video text-orange-400"></i> Trailer
                    </button>
                @endif
                @if(request()->cookie('aura_admin'))
                    <a href="{{ route('movies.edit', $movie) }}" class="btn-aura">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if($movie->episodes->isNotEmpty())
        <section class="mb-10" id="episodes">
            <h2 class="mb-3 border-l-4 border-orange-500 pl-3 text-base font-bold uppercase tracking-wide text-orange-400">Episodes</h2>
            <div class="grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10">
                @foreach($movie->episodes as $episode)
                    @php
                        $epEmbed = \App\Support\EmbedUrl::normalize($episode->url) ?: $episode->url;
                    @endphp
                    <button type="button"
                            data-episode-btn
                            data-embed-src="{{ $epEmbed }}"
                            data-embed-title="{{ $movie->title }} - Episode {{ $episode->number }}"
                            aria-label="Watch episode {{ $episode->number }}"
                            class="flex items-center justify-center gap-1.5 rounded-md bg-white/5 py-3 text-sm font-semibold text-slate-200 ring-1 ring-white/5 transition hover:bg-orange-500 hover:text-white">
                        <i class="fa-solid fa-play text-xs text-orange-400"></i> Ep {{ $episode->number }}
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    @if($movie->trailer_url)
        {{-- Trailer pop-up: opens from the Trailer button; the file is only requested when you open it. --}}
        <dialog data-trailer-dialog aria-label="{{ $movie->title }} trailer"
                class="w-[min(94vw,60rem)] overflow-hidden rounded-2xl border border-white/10 bg-black p-0 text-white shadow-2xl shadow-orange-500/10">
            <div class="flex items-center justify-between gap-3 border-b border-white/10 bg-zinc-950 px-4 py-3">
                <p class="flex min-w-0 items-center gap-2 text-sm font-bold"><i class="fa-solid fa-video text-orange-400"></i><span class="truncate">{{ $movie->title }} &middot; Trailer</span></p>
                <button type="button" data-trailer-close class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-white/10 transition hover:bg-orange-500" aria-label="Close trailer"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <video data-trailer-video data-src="{{ $movie->trailer_file_url }}" preload="none" playsinline controls
                   @if($movie->image) poster="{{ $movie->poster_url }}" @endif
                   class="aspect-video max-h-[75vh] w-full bg-black object-contain"></video>
        </dialog>
    @endif

    @php
        $firstEpisode = $movie->episodes->first();
        $defaultPlayerSrc = $movie->embed_url ?: ($firstEpisode ? (\App\Support\EmbedUrl::normalize($firstEpisode->url) ?: $firstEpisode->url) : null);
    @endphp

    @if($defaultPlayerSrc)
        <section class="mt-10" id="watch">
            <h2 class="mb-3 text-base font-bold" id="watch-title">Watch now</h2>
            <div data-embed class="mx-auto w-full max-w-4xl">
                <button type="button" data-embed-load data-embed-src="{{ $defaultPlayerSrc }}" data-embed-title="{{ $movie->title }}"
                        class="grid aspect-video w-full place-items-center rounded-lg bg-black ring-1 ring-white/10 transition hover:ring-orange-400/60"
                        aria-label="Load the player for {{ $movie->title }}">
                    <span class="flex items-center gap-2 rounded-full bg-orange-500 px-7 py-3 text-sm font-bold text-white">
                        <i class="fa-solid fa-play"></i> Watch now
                    </span>
                </button>
            </div>
        </section>
    @endif

    @php
        $episodeSubs = $movie->episodes->filter(fn ($e) => filled($e->subtitle_url));
    @endphp

    {{-- Download & Subtitles Section (shown even when there is no "Watch now" player) --}}
    @if($movie->download_480p || $movie->download_720p || $movie->download_1080p || $movie->subtitle_url || $episodeSubs->isNotEmpty())
        <section class="mt-10" id="downloads">
                <div class="mx-auto grid w-full max-w-5xl grid-cols-1 gap-6 md:grid-cols-2">
                    
                    {{-- 1. Select Quality Box (Left) --}}
                    @if($movie->download_480p || $movie->download_720p || $movie->download_1080p)
                    <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-lg">
                        <h3 class="mb-5 flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-200">
                            <i class="fa-solid fa-hard-drive text-orange-500"></i> Select Quality
                        </h3>
                        
                        <div class="space-y-3">
                            @if($movie->download_720p)
                                <a href="{{ str_replace('/e/', '/v/', $movie->download_720p) }}" target="_blank" class="group flex items-center justify-between rounded-lg bg-black/40 p-3 ring-1 ring-white/5 transition hover:bg-black/60 hover:ring-orange-500/50">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 flex-col items-center justify-center rounded bg-white/5 font-bold text-slate-300 transition group-hover:bg-orange-500/10 group-hover:text-orange-400">
                                            <span class="text-[10px] leading-none text-slate-500">HD</span>
                                            <span class="mt-0.5 text-sm">720p</span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-200 transition group-hover:text-white">Direct Download</div>
                                            <div class="mt-0.5 flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
                                                <span class="rounded bg-white/10 px-1.5 py-0.5">WEB</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid h-10 w-10 place-items-center rounded-full bg-white/5 text-slate-400 transition group-hover:bg-orange-500 group-hover:text-white">
                                        <i class="fa-solid fa-download"></i>
                                    </div>
                                </a>
                            @endif

                            @if($movie->download_1080p)
                                <a href="{{ str_replace('/e/', '/v/', $movie->download_1080p) }}" target="_blank" class="group flex items-center justify-between rounded-lg bg-black/40 p-3 ring-1 ring-white/5 transition hover:bg-black/60 hover:ring-orange-500/50">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 flex-col items-center justify-center rounded bg-white/5 font-bold text-slate-300 transition group-hover:bg-orange-500/10 group-hover:text-orange-400">
                                            <span class="text-[10px] leading-none text-slate-500">FHD</span>
                                            <span class="mt-0.5 text-sm">1080p</span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-200 transition group-hover:text-white">Direct Download</div>
                                            <div class="mt-0.5 flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
                                                <span class="rounded bg-white/10 px-1.5 py-0.5 text-emerald-500/80">HQ</span>
                                                <span class="rounded bg-orange-500/10 px-1.5 py-0.5 text-orange-400">10-BIT</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid h-10 w-10 place-items-center rounded-full bg-white/5 text-slate-400 transition group-hover:bg-orange-500 group-hover:text-white">
                                        <i class="fa-solid fa-download"></i>
                                    </div>
                                </a>
                            @endif

                            @if($movie->download_480p)
                                <a href="{{ str_replace('/e/', '/v/', $movie->download_480p) }}" target="_blank" class="group flex items-center justify-between rounded-lg bg-black/40 p-3 ring-1 ring-white/5 transition hover:bg-black/60 hover:ring-orange-500/50">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 flex-col items-center justify-center rounded bg-white/5 font-bold text-slate-300 transition group-hover:bg-orange-500/10 group-hover:text-orange-400">
                                            <span class="text-[10px] leading-none text-slate-500">SD</span>
                                            <span class="mt-0.5 text-sm">480p</span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-200 transition group-hover:text-white">Direct Download</div>
                                            <div class="mt-0.5 flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
                                                <span class="rounded bg-white/10 px-1.5 py-0.5">WEB</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid h-10 w-10 place-items-center rounded-full bg-white/5 text-slate-400 transition group-hover:bg-orange-500 group-hover:text-white">
                                        <i class="fa-solid fa-download"></i>
                                    </div>
                                </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- 2. Subtitles Box (Right) - Only shows if subtitle link is added --}}
                    @if($movie->subtitle_url || $episodeSubs->isNotEmpty())
                        <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-lg">
                            <h3 class="mb-5 flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-200">
                                <i class="fa-regular fa-closed-captioning text-orange-500"></i> Subtitles
                            </h3>
                            
                            <div class="space-y-3">
                                @if($movie->subtitle_url)
                                <a href="{{ $movie->subtitle_url }}" target="_blank" class="group flex items-center justify-between rounded-lg bg-black/40 p-3 ring-1 ring-white/5 transition hover:bg-black/60 hover:ring-orange-500/50">
                                    <div class="flex items-center gap-4">
                                        <div class="grid h-12 w-12 place-items-center rounded bg-orange-500/10 text-xl text-orange-400 transition group-hover:bg-orange-500/20">
                                            <i class="fa-regular fa-file-lines"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-200 transition group-hover:text-white">Sinhala Subtitle</div>
                                            <div class="mt-0.5 text-[10px] font-semibold uppercase text-slate-500">BY AuraCinema</div>
                                        </div>
                                    </div>
                                    <div class="grid h-10 w-10 place-items-center rounded-full bg-white/5 text-slate-400 transition group-hover:bg-orange-500 group-hover:text-white">
                                        <i class="fa-solid fa-download"></i>
                                    </div>
                                </a>
                                @endif

                                {{-- Per-episode subtitles (series) --}}
                                @if($episodeSubs->isNotEmpty())
                                    <div>
                                        <div class="mb-2 text-[10px] font-semibold uppercase text-slate-500">Episode subtitles</div>
                                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                                            @foreach($episodeSubs as $episode)
                                                <a href="{{ $episode->subtitle_url }}" target="_blank" rel="noopener"
                                                   class="group flex items-center justify-center gap-1.5 rounded-md bg-black/40 py-2.5 text-xs font-semibold text-slate-200 ring-1 ring-white/5 transition hover:bg-orange-500 hover:text-white">
                                                    <i class="fa-solid fa-download text-[10px] text-orange-400 group-hover:text-white"></i> Ep {{ $episode->number }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        {{-- Subtitle එකක් නැති විට UI එක සමබර කිරීමට හිස් ඩිව් එකක් --}}
                        <div></div>
                    @endif

                </div>
        </section>
    @endif
@endsection