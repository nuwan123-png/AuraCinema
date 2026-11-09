@extends('layouts.app')

@section('title', 'AuraCinema')

@section('content')

{{-- Animated hero carousel: up to 5 latest titles with a poster. Behaviour lives in resources/js/app.js. --}}
@if($heroes->isNotEmpty())
    <section data-hero aria-roledescription="carousel" aria-label="Latest titles"
             class="relative mb-8 touch-pan-y overflow-hidden rounded-xl sm:mb-10 bg-gradient-to-r from-zinc-900 via-[#14141c] to-zinc-900 ring-1 ring-white/10">
        <div class="hero-stage">
            @foreach($heroes as $i =>$hero)
                <article data-slide aria-hidden="{{ $i === 0 ? 'false' : 'true' }}"
                         class="hero-slide {{ $i === 0 ? 'is-active' : '' }} flex flex-row items-center gap-4 p-4 pb-10 sm:gap-6 sm:p-8 sm:pb-12 md:gap-10">
                    <div class="aspect-[2/3] w-28 shrink-0 overflow-hidden rounded-lg shadow-2xl ring-1 ring-white/10 sm:w-44 md:w-52">
                        {{-- The first poster is above the fold: eager + high priority. The rest load lazily. --}}
                        <img src="{{ $hero->poster_url }}" alt="{{ $hero->title }}" width="480" height="720"
                             @if($i === 0) fetchpriority="high" @else loading="lazy" @endif
                             decoding="async" class="h-full w-full object-cover">
                    </div>
                    <div class="min-w-0 flex-1 text-left">
                        <span class="mb-2 inline-block rounded-full bg-orange-500/15 px-2.5 py-0.5 text-[10px] sm:mb-3 sm:px-3 sm:py-1 sm:text-[11px] font-semibold text-orange-300">
                            {{ $i === 0 ? 'Latest addition' :$hero->type_label }}
                            @if($hero->episodes_count) &middot; {{$hero->episodes_count }} EPS @endif
                        </span>
                        @if($i === 0)
                            <h1 class="mb-2 line-clamp-2 text-lg font-extrabold leading-tight tracking-tight sm:mb-3 sm:text-3xl md:text-4xl">{{ $hero->title }}</h1>
                        @else
                            <p class="mb-2 line-clamp-2 text-lg font-extrabold leading-tight tracking-tight sm:mb-3 sm:text-3xl md:text-4xl">{{ $hero->title }}</p>
                        @endif
                        <p class="mb-3 line-clamp-3 max-w-2xl text-xs leading-relaxed text-slate-300 sm:mb-6 sm:line-clamp-4 sm:text-sm">{{ $hero->description ?: 'No synopsis provided for this title.' }}</p>
                        <a href="{{ route('movies.show', $hero) }}" class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 text-xs font-bold sm:px-6 sm:py-2.5 sm:text-sm text-black hover:bg-slate-200">
                            <i class="fa-solid fa-play text-xs"></i> {{ $hero->episodes_count ? 'Episodes & details' : 'Play trailer & details' }}
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        @if($heroes->count() > 1)
            <button type="button" data-hero-prev aria-label="Previous title" class="absolute left-2 top-1/2 hidden h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-black/40 text-sm hover:bg-black/70 md:grid"><i class="fa-solid fa-chevron-left"></i></button>
            <button type="button" data-hero-next aria-label="Next title" class="absolute right-2 top-1/2 hidden h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-black/40 text-sm hover:bg-black/70 md:grid"><i class="fa-solid fa-chevron-right"></i></button>

            <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-2">
                @foreach($heroes as$i => $hero) <button type="button" data-dot aria-label="Show title {{ $