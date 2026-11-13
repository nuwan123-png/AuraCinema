{{-- One episode: number + Telegram/Streamtape link + optional subtitle link. $i is the array index (the JS passes "__i__" for rows it adds). --}}
<div data-episode-row class="rounded-md border border-white/5 bg-black/20 p-2">
    <div class="flex items-center gap-2">
        <input type="number" min="1" max="9999" name="episodes[{{ $i }}][number]" value="{{ $number }}" data-episode-number aria-label="Episode number"
               class="w-20 shrink-0 rounded-md border border-white/10 bg-black/30 px-2 py-2 text-sm text-white focus:border-orange-400/60 focus:outline-none">
        <input type="url" maxlength="500" name="episodes[{{ $i }}][url]" value="{{ $url }}" placeholder="https://t.me/... (episode link)" aria-label="Link for this episode"
               class="min-w-0 flex-1 rounded-md border border-white/10 bg-black/30 px-3 py-2 text-sm text-white focus:border-orange-400/60 focus:outline-none">
        <button type="button" data-episode-remove aria-label="Remove this episode" class="grid h-9 w-9 shrink-0 place-items-center rounded-md bg-white/5 text-slate-400 hover:bg-rose-500/20 hover:text-rose-300">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="mt-2 flex items-center gap-2">
        <span class="grid w-20 shrink-0 place-items-center text-orange-400" title="Subtitle"><i class="fa-regular fa-closed-captioning"></i></span>
        <input type="url" maxlength="500" name="episodes[{{ $i }}][subtitle_url]" value="{{ $subtitle ?? '' }}" placeholder="https://... (Sinhala subtitle .srt link - optional)" aria-label="Subtitle link for this episode"
               class="min-w-0 flex-1 rounded-md border border-white/10 bg-black/30 px-3 py-2 text-sm text-white focus:border-orange-400/60 focus:outline-none">
        <span class="w-9 shrink-0"></span>
    </div>
    
    <div class="mt-2 flex items-center bg-gray-900 rounded-md border border-gray-700 overflow-hidden">
        <div class="px-3 py-2 bg-gray-800 text-gray-400 border-r border-gray-700">
            <i class="fas fa-download text-sm"></i>
        </div>
        <input type="url" 
            name="episodes[{{ $index ?? 0 }}][download_url]" 
            value="{{ $episode->download_url ?? '' }}" 
            class="flex-1 bg-transparent border-0 text-gray-300 px-3 py-2 text-sm focus:ring-0" 
            placeholder="https://... (Download link - optional)">
    </div>
</div>
