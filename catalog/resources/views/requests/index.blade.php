@extends('layouts.app')

@section('title', 'Requests')
@section('robots', 'noindex,nofollow')

@section('content')
    <h1 class="mb-4 text-lg font-bold">Requests <span class="text-sm font-normal text-slate-400">({{ $requests->count() }})</span></h1>

    @forelse($requests as $item)
        <div class="mb-2 flex items-start justify-between gap-4 rounded-md border border-white/10 bg-zinc-900/60 p-3">
            <div class="min-w-0">
                <p class="font-semibold text-white">{{ $item->title }}</p>
                @if($item->note)
                    <p class="mt-1 whitespace-pre-line text-sm text-slate-400">{{ $item->note }}</p>
                @endif
                <p class="mt-1 text-xs text-slate-500">{{ $item->created_at->format('d M Y, H:i') }}</p>
            </div>
            <form action="{{ route('requests.destroy', $item) }}" method="POST" data-confirm="Remove this request?">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-rose-400 hover:text-rose-300"><i class="fa-solid fa-trash-can"></i> Remove</button>
            </form>
        </div>
    @empty
        <p class="py-16 text-center text-sm text-slate-400">No requests yet.</p>
    @endforelse
@endsection
