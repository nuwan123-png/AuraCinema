@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
        <div class="lg:col-span-1">
            @if(!empty($movie->poster_path))
                <img src="{{ $movie->poster_path }}" alt="{{ $movie->title }}" class="rounded-xl shadow-xl w-full">
            @else
                <div class="w-full h-80 bg-neutral-800 rounded-xl flex items-center justify-center text-neutral-500">
                    No Poster Available
                </div>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-4">
            <h1 class="text-3xl font-extrabold text-white">{{ $movie->title }}</h1>
            <div class="flex items-center space-x-4 text-sm text-neutral-400">
                @if($movie->release_year)
                    <span>{{ $movie->release_year }}</span>
                @endif
                @if($movie->duration_minutes)
                    <span>{{ $movie->duration_minutes }} min</span>
                @endif
            </div>

            <p class="text-neutral-300 leading-relaxed">{{ $movie->description }}</p>

            @if($movie->embed_url)
                <div class="pt-4">
                    <button id="watch-now-trigger" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-semibold rounded-lg shadow-sm text-white bg-red-600 hover:bg-red-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                        </svg>
                        Watch Now
                    </button>
                </div>
            @endif
        </div>
    </div>

    @if($movie->embed_url)
        <div class="mt-8">
            <h2 class="text-xl font-bold text-white mb-4">Player</h2>
            <div id="watch-now-player-wrapper" data-embed-url="{{ $movie->embed_url }}" class="w-full"></div>
        </div>
    @endif
</div>
@endsection
