<div data-episode-row class="grid grid-cols-1 gap-2 rounded-md border border-white/10 bg-black/30 p-2.5 sm:grid-cols-12 sm:items-center">
    <div class="sm:col-span-1">
        <input type="number"
               name="episodes[{{ $i }}][number]"
               value="{{ $number ?? '' }}"
               min="1"
               max="9999"
               placeholder="Ep"
               aria-label="Episode number"
               class="w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-2 text-xs text-white focus:border-orange-400/60 focus:outline-none">
    </div>

    <div class="sm:col-span-3">
        <input type="url"
               name="episodes[{{ $i }}][url]"
               value="{{ $url ?? '' }}"
               maxlength="500"
               placeholder="Streamtape Embed URL (https://...)"
               aria-label="Episode stream URL"
               class="w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-2 text-xs text-white focus:border-orange-400/60 focus:outline-none">
    </div>

    <div class="sm:col-span-3">
        <input type="url"
               name="episodes[{{ $i }}][download_url]"
               value="{{ $download ?? '' }}"
               maxlength="500"
               placeholder="Direct Download URL (https://...)"
               aria-label="Episode download URL"
               class="w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-2 text-xs text-white focus:border-orange-400/60 focus:outline-none">
    </div>

    <div class="sm:col-span-2">
        <input type="url"
               name="episodes[{{ $i }}][telegram_url]"
               value="{{ $telegram ?? '' }}"
               maxlength="500"
               placeholder="Telegram Link (https://t.me/...)"
               aria-label="Episode Telegram URL"
               class="w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-2 text-xs text-white focus:border-sky-400/60 focus:outline-none">
    </div>

    <div class="sm:col-span-2">
        <input type="url"
               name="episodes[{{ $i }}][subtitle_url]"
               value="{{ $subtitle ?? '' }}"
               maxlength="500"
               placeholder="Subtitle URL (https://...)"
               aria-label="Episode subtitle URL"
               class="w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-2 text-xs text-white focus:border-orange-400/60 focus:outline-none">
    </div>

    <div class="flex justify-end sm:col-span-1">
        <button type="button"
                data-episode-remove
                aria-label="Remove episode row"
                class="grid h-8 w-8 place-items-center rounded bg-rose-500/15 text-xs text-rose-400 transition hover:bg-rose-500 hover:text-white">
            <i class="fa-solid fa-trash"></i>
        </button>
    </div>
</div>