<div class="space-y-6">
    <div>
        <label for="title" class="block text-sm font-medium text-gray-200">Movie Title</label>
        <input type="text" name="title" id="title" value="{{ old('title', $movie->title ?? '') }}" required
            class="mt-1 block w-full rounded-md border-gray-700 bg-neutral-900 text-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
        @error('title')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="embed_url" class="block text-sm font-medium text-gray-200">Watch Now Link / Embed Code</label>
        <div class="mt-1">
            <input type="text" name="embed_url" id="embed_url" value="{{ old('embed_url', $movie->embed_url ?? '') }}"
                placeholder="https://streamtape.com/v/..., https://youtu.be/..., or <iframe ...>"
                class="block w-full rounded-md border-gray-700 bg-neutral-900 text-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
        </div>
        <p class="mt-1 text-xs text-gray-400">
            Accepts YouTube, Vimeo, Bunny Stream, Cloudflare Stream, or Streamtape links & embed codes.
        </p>
        @error('embed_url')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-gray-200">Description</label>
        <textarea name="description" id="description" rows="4"
            class="mt-1 block w-full rounded-md border-gray-700 bg-neutral-900 text-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">{{ old('description', $movie->description ?? '') }}</textarea>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label for="release_year" class="block text-sm font-medium text-gray-200">Release Year</label>
            <input type="number" name="release_year" id="release_year" value="{{ old('release_year', $movie->release_year ?? '') }}"
                class="mt-1 block w-full rounded-md border-gray-700 bg-neutral-900 text-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
        </div>

        <div>
            <label for="duration_minutes" class="block text-sm font-medium text-gray-200">Duration (Minutes)</label>
            <input type="number" name="duration_minutes" id="duration_minutes" value="{{ old('duration_minutes', $movie->duration_minutes ?? '') }}"
                class="mt-1 block w-full rounded-md border-gray-700 bg-neutral-900 text-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
        </div>
    </div>
</div>
