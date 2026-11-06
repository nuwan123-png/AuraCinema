<section class="mb-8" data-row @isset($anchor) id="{{ $anchor }}" @endisset>
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-base font-bold text-white sm:text-lg">{{ $title }}</h2>
        <div class="hidden gap-1 sm:flex">
            <button type="button" data-dir="-1" aria-label="Scroll left" class="grid h-7 w-7 place-items-center rounded bg-white/5 text-xs hover:bg-white/15"><i class="fa-solid fa-chevron-left"></i></button>
            <button type="button" data-dir="1" aria-label="Scroll right" class="grid h-7 w-7 place-items-center rounded bg-white/5 text-xs hover:bg-white/15"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
    </div>
    <div data-track class="row-track -mx-1 flex snap-x gap-3 overflow-x-auto px-1 py-2 sm:gap-4">
        @foreach($items as $movie)
            @include('movies._card', ['movie' => $movie])
        @endforeach
    </div>
</section>
