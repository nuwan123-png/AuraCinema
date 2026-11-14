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

            <div class="flex flex-wrap items-start gap-3">
                @if($movie->trailer_url)
                    <button type="button" data-trailer-open class="btn-ghost" aria-haspopup="dialog">
                        <i class="fa-solid fa-video text-orange-400"></i> Trailer
                    </button>
                @endif
                {{-- Report error: opens WhatsApp with a ready message naming this movie --}}
                @php
                    $waText = 'Hi AuraCinema, I have an error in "'.$movie->title.'" movie: ';
                    $waReport = 'https://wa.me/'.preg_replace('/\D/', '', (string) config('auracinema.whatsapp_number')).'?text='.rawurlencode($waText);
                @endphp
                <div class="max-w-[16rem]">
                    <a href="{{ $waReport }}" target="_blank" rel="noopener" class="btn-ghost">
                        <i class="fa-brands fa-whatsapp text-emerald-400"></i> Report error
                    </a>
                    <p class="mt-1.5 px-1 text-xs leading-snug text-slate-400">මෙම චිත්‍රපටය පිලිබද ගැටලුවක් තිබේනම් කියන්න.</p>
                </div>
                @if(request()->cookie('aura_admin'))
                    <a href="{{ route('movies.edit', $movie) }}" class="btn-aura">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if($movie->gallery_items)
        {{-- Gallery: pictures slide sideways; click one to see it large. --}}
        <section class="mb-8 rounded-2xl border border-white/10 bg-white/[0.03] p-5 sm:p-6" id="gallery" data-row aria-label="{{ $movie->title }} pictures">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="border-l-4 border-orange-500 pl-3 text-base font-bold uppercase tracking-wide text-orange-400">Gallery</h2>
                <div class="flex gap-2">
                    <button type="button" data-dir="-1" aria-label="Scroll left" class="grid h-8 w-8 place-items-center rounded-full border border-white/10 bg-white/5 text-xs transition hover:border-orange-400/60 hover:bg-orange-500/15"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" data-dir="1" aria-label="Scroll right" class="grid h-8 w-8 place-items-center rounded-full border border-white/10 bg-white/5 text-xs transition hover:border-orange-400/60 hover:bg-orange-500/15"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
            <div data-track class="row-track -mx-1 flex snap-x snap-mandatory gap-3 overflow-x-auto px-1 py-1">
                @foreach($movie->gallery_items as $i => $item)
                    <button type="button" data-lightbox-open data-full="{{ $item['url'] }}" aria-label="Open picture {{ $i + 1 }} of {{ count($movie->gallery_items) }}"
                            class="aspect-video w-64 shrink-0 snap-start overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/10 transition hover:ring-orange-400/60 sm:w-80">
                        <img src="{{ $item['url'] }}" alt="{{ $movie->title }} picture {{ $i + 1 }}" width="640" height="360" loading="lazy" decoding="async" class="h-full w-full object-cover">
                    </button>
                @endforeach
            </div>
        </section>

        <dialog data-lightbox aria-label="{{ $movie->title }} pictures"
                class="w-[min(96vw,72rem)] overflow-hidden rounded-2xl border border-white/10 bg-black p-0 text-white shadow-2xl shadow-orange-500/10">
            <div class="relative grid place-items-center bg-black">
                <img data-lightbox-img src="" alt="" class="max-h-[85vh] w-full object-contain">
                <button type="button" data-lightbox-close aria-label="Close" class="absolute right-3 top-3 grid h-9 w-9 place-items-center rounded-full bg-black/60 transition hover:bg-orange-500"><i class="fa-solid fa-xmark"></i></button>
                <button type="button" data-lightbox-prev aria-label="Previous picture" class="absolute left-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-black/60 transition hover:bg-orange-500"><i class="fa-solid fa-chevron-left"></i></button>
                <button type="button" data-lightbox-next aria-label="Next picture" class="absolute right-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-black/60 transition hover:bg-orange-500"><i class="fa-solid fa-chevron-right"></i></button>
                <p data-lightbox-count class="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-black/60 px-3 py-1 text-xs"></p>
            </div>
        </dialog>
    @endif

    @if($movie->cast_list)
        <section class="mb-10 rounded-2xl border border-white/10 bg-white/[0.03] p-5 sm:p-6" id="cast" data-row aria-label="{{ $movie->title }} cast">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="border-l-4 border-orange-500 pl-3 text-base font-bold uppercase tracking-wide text-orange-400">Cast</h2>
                <div class="flex gap-2">
                    <button type="button" data-dir="-1" aria-label="Scroll left" class="grid h-8 w-8 place-items-center rounded-full border border-white/10 bg-white/5 text-xs transition hover:border-orange-400/60 hover:bg-orange-500/15"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" data-dir="1" aria-label="Scroll right" class="grid h-8 w-8 place-items-center rounded-full border border-white/10 bg-white/5 text-xs transition hover:border-orange-400/60 hover:bg-orange-500/15"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
            <div data-track class="row-track -mx-1 flex snap-x gap-4 overflow-x-auto px-1 py-1">
                @foreach($movie->cast_list as $member)
                    <figure class="w-28 shrink-0 snap-start text-center sm:w-32">
                        <div class="mb-2 aspect-[3/4] overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/10">
                            @if($member['photo'])
                                <img src="{{ $member['photo'] }}" alt="{{ $member['name'] }}" width="240" height="320" loading="lazy" decoding="async" class="h-full w-full object-cover">
                            @else
                                <div class="grid h-full w-full place-items-center bg-gradient-to-br from-orange-500/20 to-zinc-900 text-3xl font-extrabold text-orange-300" aria-hidden="true">{{ mb_strtoupper(mb_substr($member['name'], 0, 1)) }}</div>
                            @endif
                        </div>
                        <figcaption>
                            <span class="block text-sm font-semibold leading-tight text-white">{{ $member['name'] }}</span>
                            @if($member['role'])
                                <span class="mt-0.5 block text-xs leading-tight text-slate-400">{{ $member['role'] }}</span>
                            @endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    @if($movie->episodes->isNotEmpty())
        <section class="mb-10" id="episodes">
            <h2 class="mb-3 border-l-4 border-orange-500 pl-3 text-base font-bold uppercase tracking-wide text-orange-400">Episodes</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                @foreach($movie->episodes as $episode)
                    @php
                        $epEmbed = \App\Support\EmbedUrl::normalize($episode->url) ?: $episode->url;
                    @endphp
                    <div class="flex flex-col rounded-md bg-white/5 p-2 ring-1 ring-white/10">
                        {{-- 1. Watch Button --}}
                        <button type="button"
                                data-episode-btn
                                data-embed-src="{{ $epEmbed }}"
                                data-embed-title="{{ $movie->title }} - Episode {{ $episode->number }}"
                                aria-label="Watch episode {{ $episode->number }}"
                                class="flex w-full items-center justify-center gap-1.5 rounded bg-black/40 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-orange-500 hover:text-white">
                            <i class="fa-solid fa-play text-xs text-orange-400"></i> Ep {{ $episode->number }}
                        </button>
                        
                        {{-- 2. Download Button (Only shows if link exists) --}}
                        @if(!empty($episode->download_url))
                            <a href="{{ str_replace('/e/', '/v/', $episode->download_url) }}" 
                               target="_blank" 
                               class="mt-2 flex w-full items-center justify-center gap-1.5 rounded bg-blue-600/20 py-1.5 text-xs font-medium text-blue-400 transition hover:bg-blue-600 hover:text-white">
                                <i class="fa-solid fa-download"></i> Download
                            </a>
                        @endif
                    </div>
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