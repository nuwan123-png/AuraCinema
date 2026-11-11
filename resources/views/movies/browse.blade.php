@extends('layouts.app')

{{-- Filtered lists (navbar: Movies, Drama / Series, Anime, Genre, Year, Suggestion, and search). --}}
@section('title', strip_tags($heading))
@section('robots', 'noindex,follow')

@section('content')
    <h1 class="section-title mb-4 !text-xl sm:!text-2xl">{{ $heading }}</h1>

    {{-- Narrow the current list by language (keeps the other filters), or clear the filters. --}}
    <div class="mb-5 flex flex-wrap items-center gap-2 text-xs">
        @foreach(config('auracinema.languages') as $lang)
            @php($active = request('language') === $lang)
            <a href="{{ $active ? request()->fullUrlWithQuery(['language' => null, 'page' => null]) : request()->fullUrlWithQuery(['language' => $lang, 'page' => null]) }}"
               class="rounded-full border px-3.5 py-1.5 font-semibold transition {{ $active ? 'border-orange-400 bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow shadow-orange-500/30' : 'border-white/10 bg-white/5 text-slate-300 hover:border-orange-400/50 hover:bg-orange-500/10 hover:text-orange-200' }}">{{ $lang }}</a>
        @endforeach
        @if(request()->hasAny(['type', 'genre', 'year', 'language', 'tag', 'q']))
            <a href="{{ route('movies.index') }}" class="ml-1 text-slate-400 underline hover:text-white">Clear filters</a>
        @endif
    </div>

    @if($results->count())
        <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7">
            @foreach($results as $movie)
                @include('movies._card', ['movie' => $movie, 'class' => 'w-full'])
            @endforeach
        </div>
        @if(method_exists($results, 'hasPages'))
            @include('movies._pager', ['paginator' => $results])
        @endif
    @else
        <p class="rounded-xl border border-white/10 bg-white/[0.03] py-16 text-center text-sm text-slate-400">No titles found here yet.</p>
    @endif
@endsection
