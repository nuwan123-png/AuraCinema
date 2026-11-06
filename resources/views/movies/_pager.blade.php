@if($paginator->hasPages())
    <div class="mt-8 flex items-center justify-center gap-3 text-sm">
        @if($paginator->previousPageUrl())
            <a href="{{ $paginator->previousPageUrl() }}" class="rounded-md bg-white/5 px-4 py-2 hover:bg-white/15">&larr; Previous</a>
        @endif
        @if($paginator->nextPageUrl())
            <a href="{{ $paginator->nextPageUrl() }}" class="rounded-md bg-white/5 px-4 py-2 hover:bg-white/15">Next &rarr;</a>
        @endif
    </div>
@endif
