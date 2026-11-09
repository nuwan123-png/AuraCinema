{{-- Poster card. Images are lazy-loaded; width/height reserve space so the page does not jump. --}}
<a href="{{ route('movies.show', $movie) }}" class="group block {{ $class ?? 'w-36 shrink-0 snap-start sm:w-44' }}">
    <div class="relative aspect-[2/3] overflow-hidden rounded-md bg-zinc-900 ring-1 ring-white/10 transition duration-200 group-hover:z-10 group-hover:scale-105 group-hover:ring-white/40">
        @if($movie->image)
            <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" width="480" height="720"
                 loading="lazy" decoding="async" class="h-full w-full object-cover">
        @else
            <div class="flex h-full w-full items-center justify-center px-2 text-center text-xs text-slate-500">{{ $movie->title }}</div>
        @endif
        @if($movie->stream_url)
            <span class="absolute right-1.5 top-1.5 rounded bg-sky-500 px-1.5 py-0.5 text-[9px] font-bold text-white">FULL</span>
        @endif
        @if(($movie->episodes_count ?? 0) > 0)
            <span class="absolute left-1.5 top-1.5 rounded bg-black/75 px-1.5 py-0.5 text-[9px] font-bold text-white ring-1 ring-white/20">{{ $movie->episodes_count }} EPS</span>
        @endif
        {{-- Play icon: fades in on hover (and keyboard focus). Pure CSS, no JS. --}}
        <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/35 opacity-0 transition duration-200 group-hover:opacity-100 group-focus-visible:opacity-100" aria-hidden="true">
            <span class="flex h-12 w-12 scale-75 items-center justify-center rounded-full border-2 border-white bg-black/40 text-white transition duration-200 group-hover:scale-100 group-focus-visible:scale-100">
                <i class="fa-solid fa-play pl-0.5 text-lg"></i>
            </span>
        </span>
    </div>
    <p class="mt-2 line-clamp-1 text-xs font-medium text-slate-300 group-hover:text-white">{{ $movie->title }}</p>
    @if(in_array('sinhala sub', $movie->tag_list, true))
        <p class="mt-0.5 line-clamp-1 text-[11px] font-semibold text-orange-400">සිංහල උපසිරසි සමඟ</p>
    @endif
</a>
