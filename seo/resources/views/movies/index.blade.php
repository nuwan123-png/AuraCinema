@extends('layouts.app')

@section('title', $q !== '' ? 'Search: '.$q : 'AuraCinema')
@section('robots', $q !== '' ? 'noindex,follow' : 'index,follow')

@section('content')

@if($q !== '')
    <h1 class="mb-4 text-lg font-bold">Results for &ldquo;{{ $q }}&rdquo;</h1>
    @if($results->count())
        <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7">
            @foreach($results as $movie)
                @include('movies._card', ['movie' => $movie, 'class' => 'w-full'])
            @endforeach
        </div>
        @include('movies._pager', ['paginator' => $results])
    @else
        <p class="py-16 text-center text-sm text-slate-400">No titles match your search.</p>
    @endif
@else
    {{-- Hero: single image (no slider = no extra downloads, no JS animation cost) --}}
    @if($hero)
        <section class="mb-10 overflow-hidden rounded-xl bg-gradient-to-r from-zinc-900 via-[#14141c] to-zinc-900 ring-1 ring-white/10">
            <div class="flex flex-col items-center gap-6 p-5 sm:p-8 md:flex-row md:gap-10">
                <div class="aspect-[2/3] w-40 shrink-0 overflow-hidden rounded-lg shadow-2xl ring-1 ring-white/10 sm:w-52">
                    {{-- Above the fold: load eagerly, high priority (everything else is lazy) --}}
                    <img src="{{ $hero->poster_url }}" alt="{{ $hero->title }}" width="480" height="720"
                         fetchpriority="high" decoding="async" class="h-full w-full object-cover">
                </div>
                <div class="min-w-0 flex-1 text-center md:text-left">
                    <span class="mb-3 inline-block rounded-full bg-orange-500/15 px-3 py-1 text-[11px] font-semibold text-orange-300">Latest addition</span>
                    <h1 class="mb-3 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $hero->title }}</h1>
                    <p class="mx-auto mb-6 line-clamp-4 max-w-2xl text-sm leading-relaxed text-slate-300 md:mx-0">{{ $hero->description ?: 'No synopsis provided for this title.' }}</p>
                    <a href="{{ route('movies.show', $hero) }}" class="inline-flex items-center gap-2 rounded-md bg-white px-6 py-2.5 text-sm font-bold text-black hover:bg-slate-200">
                        <i class="fa-solid fa-play text-xs"></i> Play trailer &amp; details
                    </a>
                </div>
            </div>
        </section>
    @endif

    @if($latest->isNotEmpty())
        @include('movies._row', ['title' => 'New on AuraCinema', 'items' => $latest, 'anchor' => 'new'])
    @endif
    @if($withTrailers->isNotEmpty())
        @include('movies._row', ['title' => 'Watch the trailers', 'items' => $withTrailers])
    @endif
    @if($fullMovies->isNotEmpty())
        @include('movies._row', ['title' => 'Full movies', 'items' => $fullMovies])
    @endif

    <section id="all" class="mt-10">
        <h2 class="mb-3 text-base font-bold sm:text-lg">All titles</h2>
        @if($all->count())
            <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7">
                @foreach($all as $movie)
                    @include('movies._card', ['movie' => $movie, 'class' => 'w-full'])
                @endforeach
            </div>
            @include('movies._pager', ['paginator' => $all])
        @else
            <p class="py-16 text-center text-sm text-slate-400">No movies yet.</p>
        @endif
    </section>
@endif

@endsection
