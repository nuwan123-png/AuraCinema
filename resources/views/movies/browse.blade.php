@extends('layouts.app')

{{-- Filtered lists (navbar: Movies, Drama / Series, Anime, Genre, Year, Suggestion, and search). --}}
@section('title', strip_tags($heading))
@section('robots', 'noindex,follow')

@section('content')
    <h1 class="mb-3 text-lg font-bold">{{ $heading }}</h1>

    {{-- Narrow the current list by language (keeps the other filters), or clear the filters. --}}
    <div class="mb-5 flex flex-wrap items-center gap-2 text-xs">
        @foreach(config('auracinema.languages') as $lang)
            @php($active = request('language') === $lang)
            <a href="{{ $active ? request()->fullUrlWithQuery(['language' => null, 'page' => null]) : request()->fullUrlWithQuery(['language' => $lang, 'page' => null]) }}"
               class="rounded-full px-3 py-1 font-semibold {{ $active ? 'bg-orange-500 text-white' : 'bg-white/10 text-slate-300 hover:bg-white/20' }}">{{ $lang }}</a>
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
        <p class="py-16 text-center text-sm text-slate-400">No titles found here yet.</p>
    @endif
@endsection
