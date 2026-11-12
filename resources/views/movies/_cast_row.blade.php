{{-- One cast member: photo, name, role. $i is the array index (the JS passes "__i__" for rows it adds). --}}
<div data-cast-row class="flex items-start gap-2 rounded-md border border-white/10 bg-black/20 p-2">
    <div class="shrink-0">
        @if(!empty($photoUrl))
            <img src="{{ $photoUrl }}" alt="" width="48" height="48" loading="lazy" class="mb-1 h-12 w-12 rounded-md object-cover ring-1 ring-white/10">
        @endif
        <input type="hidden" name="cast[{{ $i }}][existing_photo]" value="{{ $photo ?? '' }}">
    </div>
    <div class="min-w-0 flex-1 space-y-1.5">
        <div class="flex gap-1.5">
            <input type="text" maxlength="80" name="cast[{{ $i }}][name]" value="{{ $name ?? '' }}" placeholder="Actor name" aria-label="Actor name"
                   class="min-w-0 flex-1 rounded-md border border-white/10 bg-black/30 px-3 py-2 text-sm text-white focus:border-orange-400/60 focus:outline-none">
            <input type="text" maxlength="80" name="cast[{{ $i }}][role]" value="{{ $role ?? '' }}" placeholder="Role (optional)" aria-label="Role"
                   class="min-w-0 flex-1 rounded-md border border-white/10 bg-black/30 px-3 py-2 text-sm text-white focus:border-orange-400/60 focus:outline-none">
        </div>
        <input type="file" name="cast[{{ $i }}][photo]" accept="image/jpeg,image/png,image/webp" aria-label="Actor photo"
               class="w-full rounded-md border border-white/10 bg-black/30 p-1.5 text-xs text-slate-400">
    </div>
    <button type="button" data-cast-remove aria-label="Remove this cast member" class="grid h-9 w-9 shrink-0 place-items-center rounded-md bg-white/5 text-slate-400 hover:bg-rose-500/20 hover:text-rose-300">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>
