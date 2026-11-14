{{-- Poster card. Images are lazy-loaded; width/height reserve space so the page does not jump. --}}
@php
    $incomplete = request()->cookie('aura_admin') && $movie->isIncomplete();
    $hasTrailer = filled($movie->trailer_url);
    $isUpcomingSection = isset($isUpcoming) && $isUpcoming;
@endphp
<div class="group relative {{ $class ?? 'w-36 shrink-0 snap-start sm:w-44' }}">
<a href="{{ route('movies.show', $movie) }}" class="block">
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

        <div class="absolute left-1.5 top-1.5 flex flex-col items-start gap-1 z-30">
            @if($movie->isNew())
                <span class="rounded-full bg-emerald-500 px-2 py-0.5 text-[9px] font-extrabold tracking-wide text-white shadow">NEW</span>
            @endif
            @if(($movie->episodes_count ?? 0) > 0)
                <span class="rounded-full bg-black/70 px-2 py-0.5 text-[9px] font-bold text-white ring-1 ring-white/20 backdrop-blur">{{ $movie->episodes_count }} EPS</span>
            @endif
        </div>

        {{-- Year and language: a small strip along the bottom; it fades out on hover to make room for the Trailer button --}}
        @if($movie->year || $movie->language)
            <div class="pointer-events-none absolute inset-x-0 {{ $incomplete ? 'bottom-8' : 'bottom-0' }} flex items-center gap-1.5 bg-gradient-to-t from-black/85 to-transparent px-2 pb-1.5 pt-6 text-[10px] font-bold text-white transition duration-200 group-hover:opacity-0 group-focus-within:opacity-0 z-20">
                @if($movie->year)<span>{{ $movie->year }}</span>@endif
                @if($movie->year && $movie->language)<span class="text-white/40">&bull;</span>@endif
                @if($movie->language)<span class="truncate uppercase tracking-wide text-orange-300">{{ $movie->language }}</span>@endif
            </div>
        @endif

        {{-- Admin only: warns when some form fields are still empty --}}
        @if($incomplete)
            <span title="Missing: {{ implode(', ', $movie->missingFields()) }}"
                  class="absolute inset-x-1.5 bottom-1.5 z-40 truncate rounded-md bg-rose-600/95 px-2 py-1 text-center text-[10px] font-extrabold uppercase tracking-wide text-white shadow">
                <i class="fa-solid fa-triangle-exclamation"></i> Incomplete
            </span>
        @endif

        @if($isUpcomingSection)
            {{-- Hover effect for Upcoming (Watch the trailers) section --}}
            <span class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center bg-black/85 p-2 text-center opacity-0 transition duration-200 group-hover:opacity-100 group-focus-within:opacity-100 z-30" aria-hidden="true">
                <i class="fa-solid fa-clock text-orange-400 text-2xl mb-2"></i>
                <span class="text-white text-xs sm:text-sm font-bold leading-snug">
                    මෙම චිත්‍රපටය තවම<br>අප්ලෝඩ් කර නැත
                </span>
            </span>
        @else
            {{-- Original Play icon and label badge: fades in on hover --}}
            <span class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-t from-black/80 via-black/40 to-black/20 p-2 text-center opacity-0 transition duration-200 group-hover:opacity-100 group-focus-within:opacity-100 z-30" aria-hidden="true">
                <span class="flex h-11 w-11 scale-75 items-center justify-center rounded-full border-2 border-orange-400 bg-orange-500/80 text-white shadow-lg shadow-orange-500/40 transition duration-200 group-hover:scale-100 group-focus-within:scale-100">
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
        @endif
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

{{-- Quick trailer: a sibling of the link (a button inside a link is invalid HTML). Shown on hover or keyboard focus; touch users tap through to the page. --}}
@if($hasTrailer)
    <div class="pointer-events-none absolute left-0 top-0 z-40 flex aspect-[2/3] w-full items-end justify-center p-2 {{ $incomplete ? 'pb-9' : '' }}">
        <button type="button" data-card-trailer data-src="{{ $movie->trailer_file_url }}" data-title="{{ $movie->title }}"
                class="pointer-events-auto hidden translate-y-1 items-center gap-1.5 rounded-full bg-black/75 px-3 py-1.5 text-[11px] font-bold text-white opacity-0 ring-1 ring-white/25 backdrop-blur transition duration-200 hover:bg-orange-500 focus-visible:translate-y-0 focus-visible:opacity-100 group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:translate-y-0 group-focus-within:opacity-100 [@media(hover:hover)]:inline-flex"
                aria-label="Watch {{ $movie->title }} trailer" aria-haspopup="dialog">
            <i class="fa-solid fa-video text-orange-300 group-hover:text-white"></i> Trailer
        </button>
    </div>

    @once
        @push('modals')
            <dialog data-card-trailer-dialog aria-label="Trailer"
                    class="w-[min(94vw,60rem)] overflow-hidden rounded-2xl border border-white/10 bg-black p-0 text-white shadow-2xl shadow-orange-500/10">
                <div class="flex items-center justify-between gap-3 border-b border-white/10 bg-zinc-950 px-4 py-3">
                    <p class="flex min-w-0 items-center gap-2 text-sm font-bold"><i class="fa-solid fa-video text-orange-400"></i><span data-card-trailer-title class="truncate"></span></p>
                    <button type="button" data-card-trailer-close class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-white/10 transition hover:bg-orange-500" aria-label="Close trailer"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <video preload="none" playsinline controls class="aspect-video max-h-[75vh] w-full bg-black object-contain"></video>
            </dialog>
        @endpush
    @endonce
@endif
</div>