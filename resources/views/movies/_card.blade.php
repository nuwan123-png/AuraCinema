{{-- Poster card. Images are lazy-loaded; width/height reserve space so the page does not jump. --}}
<a href="{{ route('movies.show', $movie) }}" class="group block {{ $class ?? 'w-36 shrink-0 snap-start sm:w-44' }}">
    <div class="relative aspect-[2/3] w-full overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/10 transition duration-300 group-hover:z-10 group-hover:-translate-y-1 group-hover:scale-[1.04] group-hover:shadow-[0_14px_34px_-8px_rgba(249,115,22,0.45)] group-hover:ring-2 group-hover:ring-orange-400/70">
        @if($movie->image)
            <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" width="480" height="720"
                 loading="lazy" decoding="async" class="h-full w-full object-cover">
        @else
            <div class="flex h-full w-full items-center justify-center px-2 text-center text-xs text-slate-500">{{ $movie->title }}</div>
        @endif

        @if($movie->stream_url)
            <span class="absolute right-1.5 top-1.5 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 px-2 py-0.5 text-[9px] font-extrabold tracking-wide text-white shadow">FULL</span>
        @endif

        @if(($movie->episodes_count ?? 0) > 0)
            <span class="absolute left-1.5 top-1.5 rounded-full bg-black/70 px-2 py-0.5 text-[9px] font-bold text-white ring-1 ring-white/20 backdrop-blur">{{ $movie->episodes_count }} EPS</span>
        @endif

        {{-- Admin only: warns when some form fields are still empty --}}
        @if(request()->cookie('aura_admin') && $movie->isIncomplete())
            <span title="Missing: {{ implode(', ', $movie->missingFields()) }}"
                  class="absolute inset-x-1.5 bottom-1.5 z-20 truncate rounded-md bg-rose-600/95 px-2 py-1 text-center text-[10px] font-extrabold uppercase tracking-wide text-white shadow">
                <i class="fa-solid fa-triangle-exclamation"></i> Incomplete
            </span>
        @endif

        {{-- Play icon and label badge: fades in on hover --}}
        <span class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-t from-black/80 via-black/40 to-black/20 p-2 text-center opacity-0 transition duration-200 group-hover:opacity-100 group-focus-visible:opacity-100" aria-hidden="true">
            <span class="flex h-11 w-11 scale-75 items-center justify-center rounded-full border-2 border-orange-400 bg-orange-500/80 text-white shadow-lg shadow-orange-500/40 transition duration-200 group-hover:scale-100 group-focus-visible:scale-100">
                <i class="fa-solid fa-play pl-0.5 text-base"></i>
            </span>
            @php
                $isSinhala = in_array('sinhala', $movie->tag_list, true);
                $isSinhalaDub = in_array('sinhala dubbed', $movie->tag_list, true);
            @endphp
            <span class="mt-2 rounded-full bg-orange-600/90 px-2.5 py-0.5 text-[10px] font-semibold text-white shadow">
                {{ $isSinhala ? 'සිංහල' : ($isSinhalaDub ? 'සිංහල හඬකැවූ' : 'සිංහල උපසිරසි සමඟ') }}
            </span>
        </span>
    </div>

    {{-- Subtitle badge below card --}}
    @php($hasSub = in_array('sinhala sub', $movie->tag_list, true))
    @if($hasSub)
        <span class="mt-1.5 inline-block max-w-full truncate rounded-full bg-orange-500/15 px-2 py-0.5 text-[10px] font-bold text-orange-300 ring-1 ring-orange-500/20">සිංහල උපසිරසි සමඟ</span>
    @endif

    {{-- Title: line-clamp-2 ensures long titles don't overflow or look squeezed on mobile --}}
    <p class="{{ $hasSub ? 'mt-1' : 'mt-1.5' }} line-clamp-2 text-xs font-medium leading-snug text-slate-300 transition group-hover:text-orange-200">
        {{ $movie->title }}
    </p>
</a>