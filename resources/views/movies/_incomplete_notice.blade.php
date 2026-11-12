{{-- Admin-only warning: shown when some form fields of this title are still empty. Pass 'force' => true on admin pages. --}}
@if(($force ?? false) || request()->cookie('aura_admin'))
    @if($movie->isIncomplete())
        <div class="mb-5 rounded-xl border border-rose-500/40 bg-rose-500/10 p-4 text-sm text-rose-100">
            <p class="flex items-center gap-2 font-bold text-rose-300">
                <i class="fa-solid fa-triangle-exclamation"></i> This title is incomplete
            </p>
            <p class="mt-1 text-xs text-rose-200/90">Still empty: {{ implode(' · ', $movie->missingFields()) }}</p>
            @unless($force ?? false)
                <a href="{{ route('movies.edit', $movie) }}" class="mt-3 inline-flex items-center gap-1.5 rounded-md bg-rose-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-500">
                    <i class="fa-solid fa-pen-to-square"></i> Complete it now
                </a>
            @endunless
        </div>
    @endif
@endif
