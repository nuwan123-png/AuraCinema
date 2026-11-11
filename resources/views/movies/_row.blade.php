<section class="mb-8" data-row @if(!empty($auto)) data-auto @endif @isset($anchor) id="{{ $anchor }}" @endisset>
    <div class="mb-3 flex items-center justify-between">
        <h2 class="section-title">{{ $title }}</h2>
        <div class="hidden gap-1 sm:flex">
            <button type="button" data-dir="-1" aria-label="Scroll left" class="grid h-8 w-8 place-items-center rounded-full border border-white/10 bg-white/5 text-xs transition hover:border-orange-400/60 hover:bg-orange-500/15"><i class="fa-solid fa-chevron-left"></i></button>
            <button type="button" data-dir="1" aria-label="Scroll right" class="grid h-8 w-8 place-items-center rounded-full border border-white/10 bg-white/5 text-xs transition hover:border-orange-400/60 hover:bg-orange-500/15"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
    </div>
    <div data-track class="row-track -mx-1 flex snap-x gap-3 overflow-x-auto px-1 py-2 sm:gap-4">
        @foreach($items as $movie)
            @include('movies._card', ['movie' => $movie])
        @endforeach
    </div>
</section>