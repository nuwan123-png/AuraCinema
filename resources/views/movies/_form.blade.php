@php
    $movie = $movie ?? null;
    $hasEpisodes = (string) old('has_episodes', ($movie && $movie->episodes->isNotEmpty()) ? '1' : '0') === '1';
    $episodeRows = old('episodes') !== null
        ? array_values((array) old('episodes'))
        : ($movie ? $movie->episodes->map(fn ($e) => ['number' => $e->number, 'url' => $e->url])->all() : []);

    $popularTags = ['romantic', 'family', 'dubbed', 'new release', 'top rated', 'classic'];
    $genreTags = ['action', 'adventure', 'animation', 'comedy', 'crime', 'documentary', 'drama', 'fantasy', 'horror', 'mystery', 'romance', 'sci-fi', 'thriller', 'war'];
    $languageTags = ['hindi', 'tamil', 'malayalam', 'telugu', 'kannada', 'english', 'sinhala', 'korean', 'japanese', 'chinese'];
    $yearTags = ['2027', '2026', '2025', '2024', '2023', '2022', '2021', '2020'];
@endphp

{{-- Hidden field to preserve existing type without displaying on the form --}}
<input type="hidden" name="type" value="{{ old('type', $movie?->type ?? 'movie') }}">

<div>
    <label for="title" class="mb-1.5 block text-xs font-semibold text-slate-300">Title</label>
    <input id="title" type="text" name="title" required maxlength="255" value="{{ old('title', $movie?->title) }}" class="w-full rounded-md border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white focus:border-orange-400/60 focus:outline-none">
</div>

{{-- Keywords & Quick-add buttons --}}
<div class="space-y-2">
    <label for="tags" class="block text-xs font-semibold text-slate-300">Keywords <span class="text-slate-500">(comma separated; each one becomes a clickable filter)</span></label>
    <input id="tags" type="text" name="tags" value="{{ old('tags', is_array($movie?->tags) ? implode(', ', $movie->tags) : $movie?->tags) }}" placeholder="romantic, dubbed, 2026, sinhala" class="w-full rounded-md border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white focus:border-orange-400/60 focus:outline-none">

    <div class="space-y-2 rounded-md border border-white/5 bg-white/[0.01] p-3 text-xs">
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="mr-1 text-[11px] font-semibold text-slate-400">Popular:</span>
            @foreach($popularTags as $t)
                <button type="button" data-tag-add="{{ $t }}" class="rounded bg-white/5 px-2 py-1 text-slate-300 transition hover:bg-white/15 hover:text-white">+ {{ $t }}</button>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-1.5 border-t border-white/5 pt-2">
            <span class="mr-1 text-[11px] font-semibold text-slate-400">Genres:</span>
            @foreach($genreTags as $t)
                <button type="button" data-tag-add="{{ $t }}" class="rounded bg-white/5 px-2 py-1 text-slate-300 transition hover:bg-white/15 hover:text-white">+ {{ $t }}</button>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-1.5 border-t border-white/5 pt-2">
            <span class="mr-1 text-[11px] font-semibold text-slate-400">Languages:</span>
            @foreach($languageTags as $t)
                <button type="button" data-tag-add="{{ $t }}" class="rounded bg-white/5 px-2 py-1 text-slate-300 transition hover:bg-white/15 hover:text-white">+ {{ $t }}</button>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-1.5 border-t border-white/5 pt-2">
            <span class="mr-1 text-[11px] font-semibold text-slate-400">Years:</span>
            @foreach($yearTags as $t)
                <button type="button" data-tag-add="{{ $t }}" class="rounded bg-white/5 px-2 py-1 text-slate-300 transition hover:bg-white/15 hover:text-white">+ {{ $t }}</button>
            @endforeach
        </div>
    </div>
</div>

<div>
    <label for="description" class="mb-1.5 block text-xs font-semibold text-slate-300">Description</label>
    <textarea id="description" name="description" rows="4" maxlength="5000" class="w-full rounded-md border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white focus:border-orange-400/60 focus:outline-none">{{ old('description', $movie?->description) }}</textarea>
</div>

<div>
    <label for="image" class="mb-1.5 block text-xs font-semibold text-slate-300">Poster (JPG / PNG / WebP, max 10MB &mdash; resized automatically)</label>
    @if($movie?->image)
        <img src="{{ $movie->poster_url }}" alt="" width="40" height="60" loading="lazy" class="mb-2 h-14 w-10 rounded object-cover ring-1 ring-white/10">
    @endif
    <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full rounded-md border border-white/10 bg-black/30 p-2 text-xs text-slate-400">
</div>

<div>
    <label for="trailer" class="mb-1.5 block text-xs font-semibold text-slate-300">Trailer (MP4 / WebM / MOV / MKV, max 30MB &mdash; compressed automatically)</label>
    @if($movie?->trailer_url)
        <p class="mb-2 text-xs text-sky-400"><i class="fa-solid fa-film"></i> A trailer is already uploaded. Choose a file only to replace it.</p>
    @endif
    <input id="trailer" type="file" name="trailer" accept="video/mp4,video/webm,video/quicktime,video/x-matroska" class="w-full rounded-md border border-white/10 bg-black/30 p-2 text-xs text-slate-400">
</div>

{{-- Episodes: tick the box to add Streamtape links for each episode (series / anime) --}}
<div class="rounded-md border border-white/10 bg-black/20 p-3">
    <input type="hidden" name="has_episodes" value="0">
    <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-200">
        <input type="checkbox" name="has_episodes" value="1" data-episodes-toggle @checked($hasEpisodes) class="h-4 w-4 accent-orange-500">
        This title has episodes
    </label>
    <p class="mt-1 text-xs text-slate-500">Tick this for a series or anime, then add a Streamtape embed link for each episode.</p>

    <div data-episodes-panel class="{{ $hasEpisodes ? '' : 'hidden' }} mt-4 space-y-3">
        <div data-episode-list class="space-y-2">
            @foreach($episodeRows as $i => $row)
                @include('movies._episode_row', ['i' => $i, 'number' => $row['number'] ?? '', 'url' => $row['url'] ?? ''])
            @endforeach
        </div>

        <template data-episode-template>
            @include('movies._episode_row', ['i' => '__i__', 'number' => '', 'url' => ''])
        </template>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" data-episode-add class="rounded-md bg-white/10 px-3 py-2 text-xs font-semibold text-white hover:bg-white/20"><i class="fa-solid fa-plus"></i> Add episode</button>
            <span class="text-xs text-slate-500">or add</span>
            <input type="number" min="1" max="200" value="12" data-episode-bulk-count aria-label="How many episode rows to add" class="w-16 rounded-md border border-white/10 bg-black/30 px-2 py-2 text-xs text-white focus:border-orange-400/60 focus:outline-none">
            <button type="button" data-episode-bulk class="rounded-md bg-white/10 px-3 py-2 text-xs font-semibold text-white hover:bg-white/20">rows at once</button>
        </div>
        <p class="text-xs text-slate-500">Empty rows are ignored. Episode numbers must be different from each other.</p>
    </div>
</div>

<div data-stream-field class="{{ $hasEpisodes ? 'hidden' : '' }}">
    <label for="embed_url" class="mb-1.5 block text-xs font-semibold text-slate-300"><i class="fa-solid fa-play text-orange-400"></i> Watch now (Streamtape, YouTube, Vimeo, Bunny Stream or Cloudflare Stream)</label>
    <input id="embed_url" type="text" name="embed_url" maxlength="800" value="{{ old('embed_url', $movie?->embed_url) }}" placeholder="Paste the player link or the whole iframe code"
           class="w-full rounded-md border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white focus:border-orange-400/60 focus:outline-none">
    <p class="mt-1 text-xs text-slate-500">The player loads when a visitor clicks "Watch now".</p>
</div>

<div class="mt-6 space-y-4 rounded-lg border border-white/10 bg-white/5 p-4">
    <h3 class="text-sm font-bold text-orange-400">Download Links (Streamtape)</h3>
    
    <div>
        <label for="download_480p" class="mb-1 block text-xs font-semibold">480p Download Link</label>
        <input type="url" name="download_480p" id="download_480p" value="{{ old('download_480p', $movie->download_480p ?? '') }}" class="w-full rounded-md border border-white/10 bg-zinc-900 px-3 py-2 text-sm text-white focus:border-orange-500 focus:outline-none" placeholder="https://streamtape.com/v/...">
        @error('download_480p') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="download_720p" class="mb-1 block text-xs font-semibold">720p Download Link</label>
        <input type="url" name="download_720p" id="download_720p" value="{{ old('download_720p', $movie->download_720p ?? '') }}" class="w-full rounded-md border border-white/10 bg-zinc-900 px-3 py-2 text-sm text-white focus:border-orange-500 focus:outline-none" placeholder="https://streamtape.com/v/...">
        @error('download_720p') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="download_1080p" class="mb-1 block text-xs font-semibold">1080p Download Link</label>
        <input type="url" name="download_1080p" id="download_1080p" value="{{ old('download_1080p', $movie->download_1080p ?? '') }}" class="w-full rounded-md border border-white/10 bg-zinc-900 px-3 py-2 text-sm text-white focus:border-orange-500 focus:outline-none" placeholder="https://streamtape.com/v/...">
        @error('download_1080p') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </div>
</div>