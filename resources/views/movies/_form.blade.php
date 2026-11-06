@php($movie = $movie ?? null)
<div>
    <label for="title" class="mb-1.5 block text-xs font-semibold text-slate-300">Title</label>
    <input id="title" type="text" name="title" required maxlength="255" value="{{ old('title', $movie?->title) }}" class="w-full rounded-md border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white focus:border-orange-400/60 focus:outline-none">
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
<div>
    <label for="stream_url" class="mb-1.5 block text-xs font-semibold text-slate-300"><i class="fa-brands fa-telegram text-sky-400"></i> Full movie link (https only)</label>
    <input id="stream_url" type="url" name="stream_url" maxlength="500" value="{{ old('stream_url', $movie?->stream_url) }}" placeholder="https://t.me/..." class="w-full rounded-md border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white focus:border-orange-400/60 focus:outline-none">
</div>
