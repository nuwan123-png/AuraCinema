@php
    $movie = $movie ?? null;
    $hasEpisodes = (string) old('has_episodes', ($movie && $movie->episodes->isNotEmpty()) ? '1' : '0') === '1';
    $episodeRows = old('episodes') !== null
        ? array_values((array) old('episodes'))
        : ($movie ? $movie->episodes->map(fn ($e) => ['number' => $e->number, 'url' => $e->url])->all() : []);
    $selectedType = old('type', $movie?->type ?? 'movie');
    $selectedGenre = old('genre', $movie?->genre);
@endphp
<div>
    <label for="title" class="mb-1.5 block text-xs font-semibold text-slate-300">Title</label>
    <input id="title" type="text" name="title" required maxlength="255" value="{{ old('title', $movie?->title) }}" class="w-full rounded-md border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white focus:border-orange-400/60 focus:outline-none">
</div>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
    <div>
        <label for="type" class="mb-1.5 block text-xs font-semibold text-slate-300">Type</label>
        <select id="type" name="type" class="w-full rounded-md border border-white/10 bg-zinc-900 px-3 py-2.5 text-sm text-white focus:border-orange-400/60 focus:outline-none">
            @foreach(config('auracinema.types') as $value => $label)
                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="genre" class="mb-1.5 block text-xs font-semibold text-slate-300">Genre</label>
        <select id="genre" name="genre" class="w-full rounded-md border border-white/10 bg-zinc-900 px-3 py-2.5 text-sm text-white focus:border-orange-400/60 focus:outline-none">
            <option value="">&mdash; none &mdash;</option>
            @foreach(config('auracinema.genres') as $g)
                <option value="{{ $g }}" @selected($selectedGenre === $g)>{{ $g }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="year" class="mb-1.5 block text-xs font-semibold text-slate-300">Year</label>
        <input id="year" type="number" name="year" min="1900" max="2100" value="{{ old('year', $movie?->year) }}" placeholder="{{ date('Y') }}" class="w-full rounded-md border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white focus:border-orange-400/60 focus:outline-none">
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

{{-- Episodes: tick the box to add one Telegram link per episode (series / anime). --}}
<div class="rounded-md border border-white/10 bg-black/20 p-3">
    <input type="hidden" name="has_episodes" value="0">
    <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-200">
        <input type="checkbox" name="has_episodes" value="1" data-episodes-toggle @checked($hasEpisodes) class="h-4 w-4 accent-orange-500">
        This title has episodes
    </label>
    <p class="mt-1 text-xs text-slate-500">Tick this for a series or anime, then add a Telegram link for each episode.</p>

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
    <label for="stream_url" class="mb-1.5 block text-xs font-semibold text-slate-300"><i class="fa-brands fa-telegram text-sky-400"></i> Full movie link (https only)</label>
    <input id="stream_url" type="url" name="stream_url" maxlength="500" value="{{ old('stream_url', $movie?->stream_url) }}" placeholder="https://t.me/..." class="w-full rounded-md border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white focus:border-orange-400/60 focus:outline-none">
</div>
