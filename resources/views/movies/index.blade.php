@extends('layouts.app')

@section('title', 'AuraCinema')

@section('content')

{{-- Animated hero carousel: up to 5 latest titles with a poster. Behaviour lives in resources/js/app.js. --}}
@if($heroes->isNotEmpty())
    <section data-hero aria-roledescription="carousel" aria-label="Latest titles"
             class="relative mb-8 touch-pan-y overflow-hidden rounded-2xl bg-gradient-to-r from-zinc-900 via-[#14141c] to-zinc-900 shadow-2xl shadow-black/50 ring-1 ring-white/10 sm:mb-10">
        <div class="hero-stage">
            @foreach($heroes as $i => $hero)
                <article data-slide aria-hidden="{{ $i === 0 ? 'false' : 'true' }}"
                         class="hero-slide {{ $i === 0 ? 'is-active' : '' }} relative flex flex-row items-center gap-4 overflow-hidden p-4 pb-10 sm:gap-6 sm:p-8 sm:pb-12 md:gap-10">
                    {{-- Soft blurred copy of the poster as a coloured backdrop --}}
                    <img src="{{ $hero->poster_url }}" alt="" aria-hidden="true" width="48" height="72" loading="lazy" decoding="async"
                         class="pointer-events-none absolute inset-0 h-full w-full scale-125 object-cover opacity-25 blur-3xl">
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-[#0b0b0f]/70 via-transparent to-transparent"></div>
                    <div class="relative aspect-[2/3] w-28 shrink-0 overflow-hidden rounded-xl shadow-2xl shadow-black/60 ring-1 ring-white/15 sm:w-44 md:w-52">
                        {{-- The first poster is above the fold: eager + high priority. The rest load lazily. --}}
                        <img src="{{ $hero->poster_url }}" alt="{{ $hero->title }}" width="480" height="720"
                             @if($i === 0) fetchpriority="high" @else loading="lazy" @endif
                             decoding="async" class="h-full w-full object-cover">
                    </div>
                    <div class="relative min-w-0 flex-1 text-left">
                        <span class="mb-2 inline-block rounded-full border border-orange-500/30 bg-orange-500/15 px-2.5 py-0.5 text-[10px] font-semibold text-orange-300 sm:mb-3 sm:px-3 sm:py-1 sm:text-[11px]">
                            {{ $i === 0 ? 'Latest addition' : $hero->type_label }}
                            @if($hero->episodes_count) &middot; {{ $hero->episodes_count }} EPS @endif
                        </span>
                        @if($i === 0)
                            <h1 class="mb-2 line-clamp-2 text-lg font-extrabold leading-tight tracking-tight sm:mb-3 sm:text-3xl md:text-4xl">{{ $hero->title }}</h1>
                        @else
                            <p class="mb-2 line-clamp-2 text-lg font-extrabold leading-tight tracking-tight sm:mb-3 sm:text-3xl md:text-4xl">{{ $hero->title }}</p>
                        @endif
                        <p class="mb-3 line-clamp-3 max-w-2xl text-xs leading-relaxed text-slate-300 sm:mb-6 sm:line-clamp-4 sm:text-sm">{{ $hero->description ?: 'No synopsis provided for this title.' }}</p>
                        <a href="{{ route('movies.show', $hero) }}" class="btn-aura !px-4 !py-2 !text-xs sm:!px-6 sm:!py-2.5 sm:!text-sm">
                            <i class="fa-solid fa-play text-xs"></i> {{ $hero->episodes_count ? 'Episodes & details' : 'Play & details' }}
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        @if($heroes->count() > 1)
            <button type="button" data-hero-prev aria-label="Previous title" class="absolute left-2 top-1/2 hidden h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-black/40 text-sm hover:bg-black/70 md:grid"><i class="fa-solid fa-chevron-left"></i></button>
            <button type="button" data-hero-next aria-label="Next title" class="absolute right-2 top-1/2 hidden h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-black/40 text-sm hover:bg-black/70 md:grid"><i class="fa-solid fa-chevron-right"></i></button>

            <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-2">
                @foreach($heroes as $i => $hero)
                    <button type="button" data-dot aria-label="Show title {{ $i + 1 }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}"
                            class="h-2 w-2 rounded-full bg-white/30 hover:bg-white/60 aria-[current=true]:w-5 aria-[current=true]:bg-orange-400"></button>
                @endforeach
            </div>

            <div class="absolute bottom-0 left-0 h-0.5 w-full bg-white/10"><div class="hero-bar h-full w-full bg-orange-500"></div></div>
        @endif
    </section>
@endif

@if($latest->isNotEmpty())
    {{-- data-auto makes this row glide by itself. Add 'auto' => true to any other row to do the same. --}}
    @include('movies._row', ['title' => 'New on AuraCinema', 'items' => $latest, 'anchor' => 'new', 'auto' => true])
@endif

{{-- $withTrailers variable now holds incomplete movies (Watch the trailers section) --}}
@if($withTrailers->isNotEmpty())
    <section class="mb-10">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white sm:text-xl">
                <i class="fa-solid fa-film text-orange-500"></i> Watch the Trailers <span class="text-sm font-normal text-gray-400">(ළඟදීම)</span>
            </h2>
        </div>
        <div class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-4 hide-scrollbar">
            @foreach($withTrailers as $movie)
                {{-- Passing isUpcoming flag to show the Sinhala message on hover --}}
                @include('movies._card', ['movie' => $movie, 'isUpcoming' => true])
            @endforeach
        </div>
    </section>
@endif

@if($fullMovies->isNotEmpty())
    @include('movies._row', ['title' => 'Full movies', 'items' => $fullMovies])
@else
    <p class="py-16 text-center text-sm text-slate-400">No movies yet.</p>
@endif

@endsection