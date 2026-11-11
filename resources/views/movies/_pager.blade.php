@if($paginator->hasPages())
    <div class="mt-8 flex items-center justify-center gap-3 text-sm">
        @if($paginator->previousPageUrl())
            <a href="{{ $paginator->previousPageUrl() }}" class="btn-ghost !py-2">&larr; Previous</a>
        @endif
        @if($paginator->nextPageUrl())
            <a href="{{ $paginator->nextPageUrl() }}" class="btn-aura !py-2">Next &rarr;</a>
        @endif
    </div>
@endif
