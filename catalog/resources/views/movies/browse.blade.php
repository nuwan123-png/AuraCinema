@extends('layouts.app')

{{-- Filtered lists (navbar: Movies, Drama / Series, Anime, Genre, Year, Suggestion, and search). --}}
@section('title', strip_tags($heading))
@section('robots', 'noindex,follow')

@section('content')
    <h1 class="mb-4 text-lg font-bold">{{ $heading }}</h1>

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
