@extends('layouts.app')

@section('title', 'Edit '.$movie->title)
@section('robots', 'noindex,nofollow')

@section('content')
<div class="mx-auto my-6 max-w-2xl rounded-xl border border-white/10 bg-zinc-900/60 p-6 sm:p-8">
    <h1 class="mb-6 text-xl font-bold">Edit title</h1>
    @include('movies._incomplete_notice', ['movie' => $movie, 'force' => true])
    <form action="{{ route('movies.update', $movie) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')
        @include('movies._form')
        <div class="flex items-center justify-between pt-3">
            <a href="{{ route('movies.show', $movie) }}" class="text-sm text-slate-400 hover:text-white">Back</a>
            <button type="submit" class="rounded-md bg-orange-500 px-6 py-2.5 text-sm font-bold text-white hover:bg-orange-600">Update</button>
        </div>
    </form>

    <form action="{{ route('movies.destroy', $movie) }}" method="POST" data-confirm="Delete this title and its files permanently?" class="mt-8 border-t border-white/10 pt-5">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-rose-400 hover:text-rose-300"><i class="fa-solid fa-trash-can"></i> Delete this title</button>
    </form>
</div>
@endsection
