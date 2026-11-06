@extends('layouts.app')

@section('title', 'Add movie')

@section('content')
<div class="mx-auto my-6 max-w-xl rounded-xl border border-white/10 bg-zinc-900/60 p-6 sm:p-8">
    <h1 class="mb-6 text-xl font-bold">Add a title</h1>
    <form action="{{ route('movies.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @include('movies._form')
        <div class="flex items-center justify-between pt-3">
            <a href="{{ route('home') }}" class="text-sm text-slate-400 hover:text-white">Cancel</a>
            <button type="submit" class="rounded-md bg-orange-500 px-6 py-2.5 text-sm font-bold text-white hover:bg-orange-600">Save</button>
        </div>
    </form>
</div>
@endsection
