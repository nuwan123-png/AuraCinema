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
    </div>
    <p class="mt-2 line-clamp-1 text-xs font-medium text-slate-300 group-hover:text-white">{{ $movie->title }}</p>
</a>
