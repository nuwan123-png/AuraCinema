{{-- One episode: number + Telegram link. $i is the array index (the JS passes "__i__" for rows it adds). --}}
<div data-episode-row class="flex items-center gap-2">
    <input type="number" min="1" max="9999" name="episodes[{{ $i }}][number]" value="{{ $number }}" data-episode-number aria-label="Episode number"
           class="w-20 shrink-0 rounded-md border border-white/10 bg-black/30 px-2 py-2 text-sm text-white focus:border-orange-400/60 focus:outline-none">
    <input type="url" maxlength="500" name="episodes[{{ $i }}][url]" value="{{ $url }}" placeholder="https://t.me/..." aria-label="Telegram link for this episode"
           class="min-w-0 flex-1 rounded-md border border-white/10 bg-black/30 px-3 py-2 text-sm text-white focus:border-orange-400/60 focus:outline-none">
    <button type="button" data-episode-remove aria-label="Remove this episode" class="grid h-9 w-9 shrink-0 place-items-center rounded-md bg-white/5 text-slate-400 hover:bg-rose-500/20 hover:text-rose-300">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>
