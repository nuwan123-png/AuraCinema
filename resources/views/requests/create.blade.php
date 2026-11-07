@extends('layouts.app')

@section('title', 'Request a title')
@section('robots', 'noindex,follow')

@section('content')
<div class="mx-auto my-6 max-w-xl rounded-xl border border-white/10 bg-zinc-900/60 p-6 sm:p-8">
    <h1 class="mb-1 text-xl font-bold">Request a title</h1>
    <p class="mb-6 text-sm text-slate-400">Can&rsquo;t find a movie or series? Tell us the name and we&rsquo;ll try to add it.</p>

    <form action="{{ route('request.store') }}" method="POST" class="space-y-4">
        @csrf
        {{-- Honeypot: hidden from people, bots fill it in and get ignored. --}}
        <div class="hidden" aria-hidden="true">
            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>
        <div>
            <label for="title" class="mb-1.5 block text-xs font-semibold text-slate-300">Title</label>
            <input id="title" type="text" name="title" required maxlength="150" value="{{ old('title') }}" class="w-full rounded-md border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white focus:border-orange-400/60 focus:outline-none">
        </div>
        <div>
            <label for="note" class="mb-1.5 block text-xs font-semibold text-slate-300">Anything else? (year, season, language &mdash; optional)</label>
            <textarea id="note" name="note" rows="3" maxlength="500" class="w-full rounded-md border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white focus:border-orange-400/60 focus:outline-none">{{ old('note') }}</textarea>
        </div>
        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('home') }}" class="text-sm text-slate-400 hover:text-white">Cancel</a>
            <button type="submit" class="rounded-md bg-orange-500 px-6 py-2.5 text-sm font-bold text-white hover:bg-orange-600">Send request</button>
        </div>
    </form>
</div>
@endsection
