@extends('layouts.app')

@section('title', $movie->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags($movie->description ?: 'Watch the trailer and details for '.$movie->title.' on AuraCinema.'), 155))
@section('og_type', 'video.movie')
@if($movie->image)
    @section('og_image', rtrim((string) config('auracinema.site_url'), '/').'/storage/'.$movie->image)
@endif
@push('jsonld')
    <script type="application/ld+json">{!! $movie->json_ld !!}</script>
@endpush

@php
    $toMobileDownloadUrl = function (?string $rawUrl): ?string {
        $url = trim((string) $rawUrl);
        if ($url === '') {
            return null;
        }
        return preg_replace(
            '~(https?://(?:www\.)?(?:streamtape\.[a-z]+|stape\.[a-z]+|strcloud\.[a-z]+))/e/~i',
            '$1/v/',
            $url
        ) ?? $url;
    };

    $toEmbedStreamtapeUrl = function (?string $rawUrl): ?string {
        $url = trim((string) $rawUrl);
        if ($url === '') {
            return null;
        }
        return preg_replace(
            '~(https?://(?:www\.)?(?:streamtape\.[a-z]+|stape\.[a-z]+|strcloud\.[a-z]+))/v/~i',
            '$1/e/',
            $url
        ) ?? $url;
    };

    $isDirectFileUrl = function (?string $rawUrl): bool {
        $path = strtolower((string) parse_url((string) $rawUrl, PHP_URL_PATH));
        return str_ends_with($path, '.mp4')
            || str_ends_with($path, '.mkv')
            || str_ends_with($path, '.webm')
            || str_ends_with($path, '.mov')
            || str_contains(strtolower((string) $rawUrl), '.r2.dev/');
    };

    $safeFileBase = \Illuminate\Support\Str::slug($movie->title) ?: 'auracinema-movie';

    $quickDownloads = [];
    if (filled($movie->download_1080p)) {
        $u = $toMobileDownloadUrl($movie->download_1080p);
        $quickDownloads[] = [
            'label' => '1080p FHD',
            'badge' => 'FHD',
            'url' => $u,
            'direct' => $isDirectFileUrl($u),
            'filename' => $safeFileBase.'-1080p.mp4',
        ];
    }
    if (filled($movie->download_720p)) {
        $u = $toMobileDownloadUrl($movie->download_720p);
        $quickDownloads[] = [
            'label' => '720p HD',
            'badge' => 'HD',
            'url' => $u,
            'direct' => $isDirectFileUrl($u),
            'filename' => $safeFileBase.'-720p.mp4',
        ];
    }
    if (filled($movie->download_480p)) {
        $u = $toMobileDownloadUrl($movie->download_480p);
        $quickDownloads[] = [
            'label' => '480p SD',
            'badge' => 'SD',
            'url' => $u,
            'direct' => $isDirectFileUrl($u),
            'filename' => $safeFileBase.'-480p.mp4',
        ];
    }
    if (filled($movie->stream_url)) {
        $u = $toMobileDownloadUrl($movie->stream_url);
        $alreadyAdded = collect($quickDownloads)->contains(fn ($d) => $d['url'] === $u);
        if (! $alreadyAdded) {
            $quickDownloads[] = [
                'label' => 'Direct MP4',
                'badge' => 'MP4',
                'url' => $u,
                'direct' => $isDirectFileUrl($u),
                'filename' => $safeFileBase.'.mp4',
            ];
        }
    }
    if (empty($quickDownloads) && filled($movie->embed_url)) {
        $u = $toMobileDownloadUrl($movie->embed_url);
        $quickDownloads[] = [
            'label' => 'Download Movie',
            'badge' => 'HD',
            'url' => $u,
            'direct' => $isDirectFileUrl($u),
            'filename' => $safeFileBase.'.mp4',
        ];
    }

    $tgAddedAt = $movie->telegram_added_at ? \Illuminate\Support\Carbon::parse($movie->telegram_added_at) : $movie->created_at;
    $isTgExpired = $tgAddedAt && $tgAddedAt->copy()->addWeeks(2)->isPast();
    $waRequestText = 'Hi AuraCinema, please re-upload "'.$movie->title.'" movie: ';
    $waRequestUrl = 'https://wa.me/'.preg_replace('/\D/', '', (string) config('auracinema.whatsapp_number')).'?text='.rawurlencode($waRequestText);
@endphp

@section('content')
    @include('movies._incomplete_notice', ['movie' => $movie])

    <nav class="mb-5 text-xs text-slate-400" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-white">Home</a> <span class="mx-1">/</span>
        <span class="text-slate-200">{{ $movie->title }}</span>
    </nav>

    @php
        // Turns the free-text description into: aligned detail rows, an optional cast line and a clean story.
        $descLines = preg_split('/\R/u', (string) $movie->description) ?: [];
        $detailRows = [];
        $castLabel = null;
        $castNames = [];
        $storyParas = [];
        $para = [];
        $flushPara = function () use (&$para, &$storyParas) {
            if ($para) { $storyParas[] = implode("\n", $para); $para = []; }
        };
        $isSymbolStart = fn (string $l) => preg_match('/^[^\p{L}\p{N}\s\-\(\"\'\[]{1,4}\s*\S/u', $l) === 1;
        foreach ($descLines as $i => $raw) {
            $line = trim($raw);
            if ($line === '') { $flushPara(); continue; }
            if ($isSymbolStart($line) && preg_match('/^(\X)\s*([^:\x{FF1A}]{1,45}?)\s*[:\x{FF1A}]\s*(.+)$/u', $line, $m)) {
                $flushPara();
                $detailRows[] = ['icon' => $m[1], 'label' => trim($m[2]), 'value' => trim($m[3])];
                continue;
            }
            if ($isSymbolStart($line) && ! str_contains($line, ':')) {
                $flushPara();
                $next = trim((string) ($descLines[$i + 1] ?? ''));
                if (str_starts_with($next, '-')) { $castLabel = trim(preg_replace('/^\X\s*/u', '', $line, 1)); continue; }
                continue;
            }
            if (str_starts_with($line, '-') && $castLabel !== null && ! $para && ! $storyParas) {
                $castNames[] = trim(ltrim($line, "- \t"));
                continue;
            }
            $para[] = $line;
        }
        $flushPara();
        $hasDetailPanel = $detailRows || $castNames;
    @endphp

    <div class="relative isolate mb-10 max-w-5xl overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] shadow-xl shadow-black/30">
        {{-- Banner: a blurred copy of the poster --}}
        <div class="relative h-32 overflow-hidden sm:h-40">
            @if($movie->image)
                <img src="{{ $movie->poster_url }}" alt="" aria-hidden="true" width="48" height="72" loading="lazy" decoding="async"
                     class="pointer-events-none absolute inset-0 h-full w-full scale-150 object-cover opacity-50 blur-2xl">
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/90 to-transparent"></div>
        </div>

        {{-- Poster overlapping the banner, title and chips next to it --}}
        <div class="relative -mt-16 flex flex-col items-center gap-4 px-5 text-center sm:-mt-20 sm:flex-row sm:items-end sm:gap-5 sm:px-6 sm:text-left">
            <div class="aspect-[2/3] w-28 shrink-0 overflow-hidden rounded-xl bg-zinc-900 shadow-2xl shadow-black/60 ring-2 ring-white/20 sm:w-36">
                @if($movie->image)
                    <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" width="480" height="720" fetchpriority="high" decoding="async" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center px-3 text-center text-sm text-slate-500">No poster</div>
                @endif
            </div>

            <div class="min-w-0 flex-1 pb-1">
                <h1 class="mb-2 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $movie->title }}</h1>
                <p class="flex flex-wrap items-center justify-center gap-1.5 text-xs sm:justify-start">
                    <a href="{{ route('movies.index', ['type' => $movie->type]) }}" class="chip">{{ $movie->type_label }}</a>
                    @if($movie->genre)
                        <a href="{{ route('movies.index', ['genre' => $movie->genre]) }}" class="chip">{{ $movie->genre }}</a>
                    @endif
                    @if($movie->language)
                        <a href="{{ route('movies.index', ['language' => $movie->language]) }}" class="chip">{{ $movie->language }}</a>
                    @endif
                    @if($movie->year)
                        <a href="{{ route('movies.index', ['year' => $movie->year]) }}" class="chip">{{ $movie->year }}</a>
                    @endif

                    @php
                        $cleanTags = array_filter(array_map('trim', explode(',', (string) $movie->tags)));
                    @endphp
                    @foreach($cleanTags as $tag)
                        <a href="{{ route('movies.index', ['tag' => mb_strtolower($tag)]) }}" class="chip-tag">#{{ $tag }}</a>
                    @endforeach

                    @if($movie->episodes->isNotEmpty())
                        <span class="rounded-full bg-gradient-to-r from-orange-500 to-orange-600 px-3 py-1 text-xs font-bold text-white">{{ $movie->episodes->count() }} EPS</span>
                    @endif
                </p>
                <p class="mt-2 text-[11px] text-slate-500">Added {{ $movie->created_at->format('d M Y') }}</p>
            </div>
        </div>

        {{-- Story + buttons on the left, aligned details on the right --}}
        <div class="grid gap-6 p-5 sm:p-6 {{ $hasDetailPanel ? 'md:grid-cols-[minmax(0,1fr)_20rem]' : '' }}">
            <div class="min-w-0">
                <div class="mb-5 max-w-2xl space-y-3 text-[13px] leading-6 text-slate-300">
                    @forelse($storyParas as $storyPara)
                        <p class="whitespace-pre-line">{{ $storyPara }}</p>
                    @empty
                        <p>No synopsis provided.</p>
                    @endforelse
                </div>

                <div class="flex flex-wrap items-start gap-2.5">
                    @if(!empty($quickDownloads))
                        @php
                            $primaryDl = $quickDownloads[0];
                        @endphp
                        <a href="{{ $primaryDl['url'] }}"
                           data-dl-notice
                           @if($primaryDl['direct']) data-dl-filename="{{ $primaryDl['filename'] }}" @endif
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn-aura !px-4 !py-2 !text-xs shadow-lg shadow-orange-500/20">
                            <i class="fa-solid fa-download"></i> Download ({{ $primaryDl['badge'] }})
                        </a>
                    @elseif($movie->telegram_url)
                        <button type="button"
                                data-tg-download
                                data-tg-title="{{ $movie->title }}"
                                data-tg-target="{{ $isTgExpired ? $waRequestUrl : $movie->telegram_url }}"
                                data-tg-expired="{{ $isTgExpired ? '1' : '0' }}"
                                class="btn-aura !px-4 !py-2 !text-xs">
                            <i class="fa-brands fa-telegram"></i> Download
                        </button>
                    @endif

                    @if($movie->trailer_embed_url)
                        <button type="button" data-yt-trailer-open class="btn-ghost !px-4 !py-2 !text-xs" aria-haspopup="dialog">
                            <i class="fa-brands fa-youtube text-rose-500"></i> Trailer
                        </button>
                    @elseif($movie->trailer_url)
                        <button type="button" data-trailer-open class="btn-ghost !px-4 !py-2 !text-xs" aria-haspopup="dialog">
                            <i class="fa-solid fa-video text-orange-400"></i> Trailer
                        </button>
                    @endif

                    {{-- Watchlist / Favorites Toggle (Synced with Mobile App) --}}
                    @if(\Illuminate\Support\Facades\Route::has('user.watchlist.toggle'))
                        @auth
                            @php
                                $inWebWatchlist = class_exists(\App\Models\Watchlist::class)
                                    && \Illuminate\Support\Facades\Schema::hasTable('watchlists')
                                    && \App\Models\Watchlist::where('user_id', auth()->id())->where('movie_id', $movie->id)->exists();
                            @endphp
                            <form method="POST" action="{{ route('user.watchlist.toggle') }}" class="inline-block">
                                @csrf
                                <input type="hidden" name="movie_id" value="{{ $movie->id }}">
                                <input type="hidden" name="title" value="{{ $movie->title }}">
                                <input type="hidden" name="poster_url" value="{{ $movie->poster_url ?? '' }}">
                                <input type="hidden" name="type" value="{{ $movie->type ?? 'movie' }}">
                                <input type="hidden" name="year" value="{{ $movie->year ?? '' }}">
                                <button type="submit"
                                        class="btn-ghost !px-4 !py-2 !text-xs {{ $inWebWatchlist ? '!border-rose-500/50 !bg-rose-500/15 !text-rose-400' : '' }}">
                                    <i class="{{ $inWebWatchlist ? 'fa-solid' : 'fa-regular' }} fa-heart text-rose-400"></i>
                                    <span>{{ $inWebWatchlist ? 'In Watchlist' : 'Add to Watchlist' }}</span>
                                </button>
                            </form>
                        @else
                            @if(\Illuminate\Support\Facades\Route::has('user.auth'))
                                <a href="{{ route('user.auth') }}" class="btn-ghost !px-4 !py-2 !text-xs">
                                    <i class="fa-regular fa-heart text-rose-400"></i>
                                    <span>Add to Watchlist</span>
                                </a>
                            @endif
                        @endauth
                    @endif

                    {{-- Report error: opens WhatsApp with a ready message naming this movie --}}
                    @php
                        $waText = 'Hi AuraCinema, I have an error in "'.$movie->title.'" movie: ';
                        $waReport = 'https://wa.me/'.preg_replace('/\D/', '', (string) config('auracinema.whatsapp_number')).'?text='.rawurlencode($waText);
                    @endphp
                    <a href="{{ $waReport }}" target="_blank" rel="noopener" class="btn-ghost !px-4 !py-2 !text-xs">
                        <i class="fa-brands fa-whatsapp text-emerald-400"></i> Report error
                    </a>

                    {{-- Share: WhatsApp share link and a copy-link button; the link always uses the public https address --}}
                    @php
                        $shareUrl = rtrim((string) config('auracinema.site_url'), '/').request()->getPathInfo();
                        $waShare = 'https://wa.me/?text='.rawurlencode('Watch "'.$movie->title.'" on AuraCinema: '.$shareUrl);
                    @endphp
                    <a href="{{ $waShare }}" target="_blank" rel="noopener" class="btn-ghost !px-4 !py-2 !text-xs">
                        <i class="fa-solid fa-share-nodes text-orange-400"></i> Share
                    </a>
                    <button type="button" data-copy-link="{{ $shareUrl }}" class="btn-ghost !px-4 !py-2 !text-xs">
                        <i class="fa-regular fa-copy text-orange-400"></i> <span data-copy-label>Copy link</span>
                    </button>
                    @if(request()->cookie('aura_admin'))
                        <a href="{{ route('movies.edit', $movie) }}" class="btn-ghost !px-4 !py-2 !text-xs">
                            <i class="fa-solid fa-pen-to-square text-orange-400"></i> Edit
                        </a>
                    @endif
                </div>
                <p class="mt-3 flex items-center gap-1.5 text-xs leading-snug text-slate-400"><i class="fa-brands fa-whatsapp text-emerald-400"></i> මෙම චිත්‍රපටය පිලිබද ගැටලුවක් තිබේනම් කියන්න.</p>
            </div>

            @if($hasDetailPanel)
                <aside class="h-fit rounded-2xl border border-white/10 bg-black/20 p-3 sm:p-4" aria-label="Details">
                    @foreach($detailRows as $row)
                        <div class="grid grid-cols-[1.5rem_6.5rem_minmax(0,1fr)] items-baseline gap-x-2 py-1.5 {{ ! $loop->first ? 'border-t border-white/5' : '' }}">
                            <span class="text-center text-sm">{{ $row['icon'] }}</span>
                            <span class="text-xs text-slate-400">{{ $row['label'] }}</span>
                            <span class="min-w-0 break-words text-sm font-medium text-slate-100">{{ $row['value'] }}</span>
                        </div>
                    @endforeach
                    @if($castNames)
                        <div class="{{ $detailRows ? 'mt-1 border-t border-white/5 pt-2.5' : '' }}">
                            <p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-orange-400">{{ $castLabel ?: 'Cast' }}</p>
                            <p class="text-sm leading-6 text-slate-200">{{ implode(' · ', $castNames) }}</p>
                        </div>
                    @endif
                </aside>
            @endif
        </div>
    </div>

    @if($movie->gallery_items)
        {{-- Gallery: pictures slide sideways; click one to see it large. --}}
        <section class="mb-8 rounded-2xl border border-white/10 bg-white/[0.03] p-5 sm:p-6" id="gallery" data-row aria-label="{{ $movie->title }} pictures">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="border-l-4 border-orange-500 pl-3 text-base font-bold uppercase tracking-wide text-orange-400">Gallery</h2>
                <div class="flex gap-2">
                    <button type="button" data-dir="-1" aria-label="Scroll left" class="grid h-8 w-8 place-items-center rounded-full border border-white/10 bg-white/5 text-xs transition hover:border-orange-400/60 hover:bg-orange-500/15"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" data-dir="1" aria-label="Scroll right" class="grid h-8 w-8 place-items-center rounded-full border border-white/10 bg-white/5 text-xs transition hover:border-orange-400/60 hover:bg-orange-500/15"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
            <div data-track class="row-track -mx-1 flex snap-x snap-mandatory gap-3 overflow-x-auto px-1 py-1">
                @foreach($movie->gallery_items as $i => $item)
                    <button type="button" data-lightbox-open data-full="{{ $item['url'] }}" aria-label="Open picture {{ $i + 1 }} of {{ count($movie->gallery_items) }}"
                            class="aspect-video w-64 shrink-0 snap-start overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/10 transition hover:ring-orange-400/60 sm:w-80">
                        <img src="{{ $item['url'] }}" alt="{{ $movie->title }} picture {{ $i + 1 }}" width="640" height="360" loading="lazy" decoding="async" class="h-full w-full object-cover">
                    </button>
                @endforeach
            </div>
        </section>

        <dialog data-lightbox aria-label="{{ $movie->title }} pictures"
                class="w-[min(96vw,72rem)] overflow-hidden rounded-2xl border border-white/10 bg-black p-0 text-white shadow-2xl shadow-orange-500/10">
            <div class="relative grid place-items-center bg-black">
                <img data-lightbox-img src="" alt="" class="max-h-[85vh] w-full object-contain">
                <button type="button" data-lightbox-close aria-label="Close" class="absolute right-3 top-3 grid h-9 w-9 place-items-center rounded-full bg-black/60 transition hover:bg-orange-500"><i class="fa-solid fa-xmark"></i></button>
                <button type="button" data-lightbox-prev aria-label="Previous picture" class="absolute left-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-black/60 transition hover:bg-orange-500"><i class="fa-solid fa-chevron-left"></i></button>
                <button type="button" data-lightbox-next aria-label="Next picture" class="absolute right-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-black/60 transition hover:bg-orange-500"><i class="fa-solid fa-chevron-right"></i></button>
                <p data-lightbox-count class="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-black/60 px-3 py-1 text-xs"></p>
            </div>
        </dialog>
    @endif

    @if($movie->cast_list)
        <section class="mb-10 rounded-2xl border border-white/10 bg-white/[0.03] p-5 sm:p-6" id="cast" data-row aria-label="{{ $movie->title }} cast">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="border-l-4 border-orange-500 pl-3 text-base font-bold uppercase tracking-wide text-orange-400">Cast</h2>
                <div class="flex gap-2">
                    <button type="button" data-dir="-1" aria-label="Scroll left" class="grid h-8 w-8 place-items-center rounded-full border border-white/10 bg-white/5 text-xs transition hover:border-orange-400/60 hover:bg-orange-500/15"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" data-dir="1" aria-label="Scroll right" class="grid h-8 w-8 place-items-center rounded-full border border-white/10 bg-white/5 text-xs transition hover:border-orange-400/60 hover:bg-orange-500/15"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
            <div data-track class="row-track -mx-1 flex snap-x gap-4 overflow-x-auto px-1 py-1">
                @foreach($movie->cast_list as $member)
                    <figure class="w-28 shrink-0 snap-start text-center sm:w-32">
                        <div class="mb-2 aspect-[3/4] overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/10">
                            @if($member['photo'])
                                <img src="{{ $member['photo'] }}" alt="{{ $member['name'] }}" width="240" height="320" loading="lazy" decoding="async" class="h-full w-full object-cover">
                            @else
                                <div class="grid h-full w-full place-items-center bg-gradient-to-br from-orange-500/20 to-zinc-900 text-3xl font-extrabold text-orange-300" aria-hidden="true">{{ mb_strtoupper(mb_substr($member['name'], 0, 1)) }}</div>
                            @endif
                        </div>
                        <figcaption>
                            <span class="block text-sm font-semibold leading-tight text-white">{{ $member['name'] }}</span>
                            @if($member['role'])
                                <span class="mt-0.5 block text-xs leading-tight text-slate-400">{{ $member['role'] }}</span>
                            @endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    @if($movie->episodes->isNotEmpty())
        <section class="mb-10" id="episodes">
            <h2 class="mb-3 border-l-4 border-orange-500 pl-3 text-base font-bold uppercase tracking-wide text-orange-400">Episodes</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                @foreach($movie->episodes as $episode)
                    @php
                        $epEmbedRaw = \App\Support\EmbedUrl::normalize($episode->url) ?: $episode->url;
                        $epEmbed = $toEmbedStreamtapeUrl($epEmbedRaw);
                        $epRawDl = filled($episode->download_url) ? $episode->download_url : $episode->url;
                        $epDlUrl = $toMobileDownloadUrl($epRawDl);
                        $epIsDirect = $isDirectFileUrl($epDlUrl);
                    @endphp
                    <div class="flex flex-col rounded-md bg-white/5 p-2 ring-1 ring-white/10">
                        {{-- 1. Watch Button --}}
                        <button type="button"
                                data-episode-btn
                                data-episode="{{ $episode->number }}"
                                data-embed-src="{{ $epEmbed }}"
                                data-embed-title="{{ $movie->title }} - Episode {{ $episode->number }}"
                                aria-label="Watch episode {{ $episode->number }}"
                                class="flex w-full items-center justify-center gap-1.5 rounded bg-black/40 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-orange-500 hover:text-white">
                            <i class="fa-solid fa-play text-xs text-orange-400"></i> Ep {{ $episode->number }}
                        </button>

                        {{-- 2. Direct Download Button --}}
                        @if(!empty($epDlUrl))
                            <a href="{{ $epDlUrl }}"
                               data-dl-notice
                               @if($epIsDirect) data-dl-filename="{{ $safeFileBase }}-ep-{{ $episode->number }}.mp4" @endif
                               target="_blank"
                               rel="noopener noreferrer"
                               class="mt-2 flex w-full items-center justify-center gap-1.5 rounded bg-blue-600/20 py-1.5 text-xs font-medium text-blue-400 transition hover:bg-blue-600 hover:text-white">
                                <i class="fa-solid fa-download"></i> Download
                            </a>
                        @endif

                        {{-- 3. Telegram Download Button --}}
                        @if(!empty($episode->telegram_url))
                            @php
                                $epAddedAt = $episode->created_at ?? $movie->created_at;
                                $isEpTgExpired = $epAddedAt && $epAddedAt->copy()->addWeeks(2)->isPast();
                                $epWaText = 'Hi AuraCinema, please re-upload "'.$movie->title.' - Episode '.$episode->number.'": ';
                                $epWaUrl = 'https://wa.me/'.preg_replace('/\D/', '', (string) config('auracinema.whatsapp_number')).'?text='.rawurlencode($epWaText);
                            @endphp
                            <button type="button"
                                    data-tg-download
                                    data-tg-title="{{ $movie->title }} - Ep {{ $episode->number }}"
                                    data-tg-target="{{ $isEpTgExpired ? $epWaUrl : $episode->telegram_url }}"
                                    data-tg-expired="{{ $isEpTgExpired ? '1' : '0' }}"
                                    class="mt-1.5 flex w-full items-center justify-center gap-1.5 rounded bg-sky-500/20 py-1.5 text-xs font-medium text-sky-400 transition hover:bg-sky-500 hover:text-white">
                                <i class="fa-brands fa-telegram"></i> Telegram
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($movie->trailer_embed_url)
        {{-- YouTube Trailer Pop-up --}}
        <dialog data-yt-trailer-dialog aria-label="{{ $movie->title }} trailer"
                class="fixed inset-0 m-auto w-[min(94vw,60rem)] overflow-hidden rounded-2xl border border-white/10 bg-black p-0 text-white shadow-2xl shadow-orange-500/10 backdrop:bg-black/80 backdrop:backdrop-blur-sm">
            <div class="flex items-center justify-between gap-3 border-b border-white/10 bg-zinc-950 px-4 py-3">
                <p class="flex min-w-0 items-center gap-2 text-sm font-bold"><i class="fa-brands fa-youtube text-rose-500"></i><span class="truncate">{{ $movie->title }} &middot; Trailer</span></p>
                <button type="button" data-yt-trailer-close class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-white/10 transition hover:bg-orange-500" aria-label="Close trailer"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="aspect-video w-full bg-black">
                <iframe data-yt-trailer-frame
                        data-src="{{ $movie->trailer_embed_url }}?autoplay=1&rel=0"
                        src=""
                        title="{{ $movie->title }} Trailer"
                        class="h-full w-full"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
            </div>
        </dialog>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const openBtn = document.querySelector('[data-yt-trailer-open]');
                const dialog = document.querySelector('[data-yt-trailer-dialog]');
                const closeBtn = document.querySelector('[data-yt-trailer-close]');
                const iframe = document.querySelector('[data-yt-trailer-frame]');

                if (!openBtn || !dialog || !iframe) return;

                openBtn.addEventListener('click', () => {
                    iframe.src = iframe.dataset.src;
                    if (typeof dialog.showModal === 'function') {
                        dialog.showModal();
                    } else {
                        dialog.setAttribute('open', '');
                    }
                });

                const closeTrailer = () => {
                    iframe.src = '';
                    if (typeof dialog.close === 'function') {
                        dialog.close();
                    } else {
                        dialog.removeAttribute('open');
                    }
                };

                closeBtn?.addEventListener('click', closeTrailer);
                dialog.addEventListener('close', () => { iframe.src = ''; });
                dialog.addEventListener('click', (e) => {
                    if (e.target === dialog) closeTrailer();
                });
            });
        </script>
    @elseif($movie->trailer_url)
        {{-- Legacy Uploaded MP4 Trailer Pop-up --}}
        <dialog data-trailer-dialog aria-label="{{ $movie->title }} trailer"
                class="w-[min(94vw,60rem)] overflow-hidden rounded-2xl border border-white/10 bg-black p-0 text-white shadow-2xl shadow-orange-500/10">
            <div class="flex items-center justify-between gap-3 border-b border-white/10 bg-zinc-950 px-4 py-3">
                <p class="flex min-w-0 items-center gap-2 text-sm font-bold"><i class="fa-solid fa-video text-orange-400"></i><span class="truncate">{{ $movie->title }} &middot; Trailer</span></p>
                <button type="button" data-trailer-close class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-white/10 transition hover:bg-orange-500" aria-label="Close trailer"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <video data-trailer-video data-src="{{ $movie->trailer_file_url }}" preload="none" playsinline controls
                   @if($movie->image) poster="{{ $movie->poster_url }}" @endif
                   class="aspect-video max-h-[75vh] w-full bg-black object-contain"></video>
        </dialog>
    @endif

    @php
        $firstEpisode = $movie->episodes->first();
        $rawDefaultSrc = $movie->embed_url ?: ($firstEpisode ? (\App\Support\EmbedUrl::normalize($firstEpisode->url) ?: $firstEpisode->url) : null);
        $defaultPlayerSrc = $toEmbedStreamtapeUrl($rawDefaultSrc);
    @endphp

    @if($defaultPlayerSrc)
        <section class="mt-10" id="watch">
            <style>
                @keyframes auraReelSpin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                @keyframes auraBuddyFloat {
                    0%, 100% { transform: translateY(0px); }
                    50% { transform: translateY(-5px); }
                }
                @keyframes auraEyeBlink {
                    0%, 44%, 48%, 100% { transform: scaleY(1); }
                    46% { transform: scaleY(0.1); }
                }
                @keyframes auraBeamPulse {
                    0%, 100% { opacity: 0.45; transform: scaleX(0.96); }
                    50% { opacity: 0.95; transform: scaleX(1.03); }
                }
                @keyframes auraStarPop {
                    0% { transform: translateY(4px) scale(0.6); opacity: 0; }
                    50% { transform: translateY(-4px) scale(1.15); opacity: 1; }
                    100% { transform: translateY(-12px) scale(0.7); opacity: 0; }
                }
                @keyframes auraProgressSlide {
                    0% { transform: translateX(-100%); }
                    100% { transform: translateX(260%); }
                }
                .aura-reel-spin {
                    transform-box: fill-box;
                    transform-origin: center;
                    animation: auraReelSpin 2.4s linear infinite;
                }
                .aura-reel-spin-fast {
                    transform-box: fill-box;
                    transform-origin: center;
                    animation: auraReelSpin 1.8s linear infinite;
                }
                .aura-buddy-float {
                    animation: auraBuddyFloat 2.2s ease-in-out infinite;
                }
                .aura-eye-blink {
                    transform-box: fill-box;
                    transform-origin: center;
                    animation: auraEyeBlink 3.6s infinite;
                }
                .aura-beam-pulse {
                    transform-box: fill-box;
                    transform-origin: left center;
                    animation: auraBeamPulse 1.8s ease-in-out infinite;
                }
                .aura-star-1 { animation: auraStarPop 2s ease-in-out infinite; }
                .aura-star-2 { animation: auraStarPop 2s ease-in-out 0.65s infinite; }
                .aura-star-3 { animation: auraStarPop 2s ease-in-out 1.3s infinite; }
                .aura-progress-bar { animation: auraProgressSlide 1.4s ease-in-out infinite; }

                @media (prefers-reduced-motion: reduce) {
                    .aura-reel-spin, .aura-reel-spin-fast, .aura-buddy-float,
                    .aura-eye-blink, .aura-beam-pulse, .aura-star-1,
                    .aura-star-2, .aura-star-3, .aura-progress-bar {
                        animation: none !important;
                    }
                }
            </style>

            <div class="mx-auto mb-3 w-full max-w-4xl">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-bold" id="watch-title">Watch now</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="auraAdShieldBtn" onclick="toggleAuraAdShield()"
                                class="inline-flex items-center gap-1.5 rounded-full border border-amber-400/40 bg-amber-500/15 px-3 py-1 text-xs font-bold text-amber-300 transition hover:bg-sky-500 hover:text-white">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span id="auraAdShieldText">Ad Shield: OFF</span>
                        </button>
                        <button type="button" data-fullscreen-btn
                                class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold text-slate-200 transition hover:bg-white/15 hover:text-white"
                                aria-label="Open the player in fullscreen">
                            <i class="fa-solid fa-expand"></i> Fullscreen
                        </button>
                        <button type="button" data-sub-guide-open
                                class="inline-flex items-center gap-1.5 rounded-full border border-orange-500/30 bg-orange-500/10 px-3 py-1 text-xs font-semibold text-orange-300 transition hover:bg-orange-500 hover:text-white">
                            <i class="fa-solid fa-closed-captioning"></i> Subtitle දාගන්න හැටි
                        </button>
                    </div>
                </div>
                <p class="mt-1.5 text-xs font-semibold leading-relaxed text-emerald-400 sm:text-sm">
                    සිදුවන අපහසුතාවයට කනගාටුයි, ads ටිකක් යනවා close කරලා බලන්න, අපි ads ගොඩක් block කරලා තියෙන්නේ.
                </p>
            </div>

            {{-- Player Box with Cute Animated Loading Overlay --}}
            <div id="auraPlayerShell" class="relative mx-auto w-full max-w-4xl overflow-hidden rounded-2xl bg-black ring-1 ring-white/10">
                <div data-embed class="aspect-video w-full bg-black">
                    <button type="button" data-embed-load data-embed-src="{{ $defaultPlayerSrc }}" data-embed-title="{{ $movie->title }}"
                            class="grid aspect-video w-full place-items-center bg-black transition hover:bg-white/[0.02]"
                            aria-label="Load the player for {{ $movie->title }}">
                        <span class="flex items-center gap-2 rounded-full bg-orange-500 px-7 py-3 text-sm font-bold text-white shadow-lg shadow-orange-500/30 transition hover:scale-105">
                            <i class="fa-solid fa-play"></i> Watch now
                        </span>
                    </button>
                </div>

                {{-- Cute Cinema Projector Buddy Loader (Shows on Watch Now & Player Play Button Click) --}}
                <div id="auraPlayerLoader"
                     aria-hidden="true"
                     class="pointer-events-none absolute inset-0 z-20 hidden flex-col items-center justify-center bg-[#090d16]/90 px-4 text-center backdrop-blur-sm transition-opacity duration-300">
                    <div class="aura-buddy-float w-48 sm:w-56">
                        <svg viewBox="0 0 220 115" class="h-auto w-full overflow-visible" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="auraBeamGrad" x1="120" y1="64" x2="210" y2="64" gradientUnits="userSpaceOnUse">
                                    <stop offset="0%" stop-color="#38BDF8" stop-opacity="0.55"/>
                                    <stop offset="60%" stop-color="#F97316" stop-opacity="0.22"/>
                                    <stop offset="100%" stop-color="#F97316" stop-opacity="0"/>
                                </linearGradient>
                                <linearGradient id="auraBodyGrad" x1="34" y1="42" x2="114" y2="94" gradientUnits="userSpaceOnUse">
                                    <stop offset="0%" stop-color="#1E293B"/>
                                    <stop offset="100%" stop-color="#0F172A"/>
                                </linearGradient>
                            </defs>

                            {{-- Light Beam --}}
                            <polygon points="122,56 210,22 210,104 122,72" fill="url(#auraBeamGrad)" class="aura-beam-pulse"/>

                            {{-- Floating Sparkles in Beam --}}
                            <circle cx="156" cy="54" r="2.3" fill="#38BDF8" class="aura-star-1"/>
                            <circle cx="182" cy="68" r="2.8" fill="#FB923C" class="aura-star-2"/>
                            <circle cx="170" cy="43" r="1.9" fill="#FDE047" class="aura-star-3"/>

                            {{-- Left Film Reel --}}
                            <g class="aura-reel-spin">
                                <circle cx="56" cy="28" r="15" fill="#131B2E" stroke="#38BDF8" stroke-width="2.2"/>
                                <circle cx="56" cy="28" r="3" fill="#38BDF8"/>
                                <circle cx="56" cy="19" r="2.2" fill="#94A3B8"/>
                                <circle cx="56" cy="37" r="2.2" fill="#94A3B8"/>
                                <circle cx="47" cy="28" r="2.2" fill="#94A3B8"/>
                                <circle cx="65" cy="28" r="2.2" fill="#94A3B8"/>
                            </g>

                            {{-- Right Film Reel --}}
                            <g class="aura-reel-spin-fast">
                                <circle cx="92" cy="28" r="15" fill="#131B2E" stroke="#F97316" stroke-width="2.2"/>
                                <circle cx="92" cy="28" r="3" fill="#F97316"/>
                                <circle cx="92" cy="19" r="2.2" fill="#94A3B8"/>
                                <circle cx="92" cy="37" r="2.2" fill="#94A3B8"/>
                                <circle cx="83" cy="28" r="2.2" fill="#94A3B8"/>
                                <circle cx="101" cy="28" r="2.2" fill="#94A3B8"/>
                            </g>

                            {{-- Projector Lens --}}
                            <rect x="110" y="53" width="13" height="22" rx="4" fill="#2563EB" stroke="#38BDF8" stroke-width="1.5"/>

                            {{-- Cute Projector Body --}}
                            <rect x="34" y="42" width="78" height="48" rx="15" fill="url(#auraBodyGrad)" stroke="rgba(255,255,255,0.22)" stroke-width="1.8"/>

                            {{-- Cute Blinking Eyes --}}
                            <g class="aura-eye-blink">
                                <circle cx="61" cy="63" r="4.2" fill="#F8FAFC"/>
                                <circle cx="62.2" cy="61.8" r="1.4" fill="#0F172A"/>
                                <circle cx="85" cy="63" r="4.2" fill="#F8FAFC"/>
                                <circle cx="86.2" cy="61.8" r="1.4" fill="#0F172A"/>
                            </g>

                            {{-- Rosy Cheeks & Happy Smile --}}
                            <ellipse cx="52" cy="70" rx="3.5" ry="2" fill="#FB7185" fill-opacity="0.55"/>
                            <ellipse cx="94" cy="70" rx="3.5" ry="2" fill="#FB7185" fill-opacity="0.55"/>
                            <path d="M 68 72 Q 73 77 78 72" stroke="#38BDF8" stroke-width="2.2" stroke-linecap="round"/>

                            {{-- Little Stand Legs --}}
                            <path d="M 56 90 L 49 103 M 90 90 L 97 103" stroke="#64748B" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </div>

                    <p id="auraPlayerFunMsg" class="mt-1 text-sm font-extrabold tracking-wide text-amber-300">
                        🍿 Popcorn ලෑස්ති කරගන්න...
                    </p>
                    <p id="auraPlayerLoaderTitle" class="mt-1 max-w-xs truncate text-xs font-medium text-slate-400">
                        {{ $movie->title }}
                    </p>

                    {{-- Mini Animated Progress Bar --}}
                    <div class="mt-3 h-1.5 w-44 overflow-hidden rounded-full bg-white/10">
                        <div class="aura-progress-bar h-full w-1/3 rounded-full bg-gradient-to-r from-sky-400 via-blue-500 to-orange-500"></div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- A-Ads Clean Banner Unit --}}
    <div class="mx-auto mt-8 w-full max-w-4xl">
        <!-- BEGIN AADS AD UNIT 2463381 -->
        <div id="frame" style="width: 100%;margin: auto;position: relative; z-index: 99998;">
            <iframe data-aa='2463381' src='//acceptable.a-ads.com/2463381/?size=Adaptive'
                    style='border:0; padding:0; width:70%; height:auto; overflow:hidden;display: block;margin: auto'></iframe>
        </div>
        <!-- END AADS AD UNIT 2463381 -->
    </div>

    @php
        $episodeSubs = $movie->episodes->filter(fn ($e) => filled($e->subtitle_url));
        $hasDownloadsBox = !empty($quickDownloads) || filled($movie->telegram_url);
        $hasSubtitlesBox = filled($movie->subtitle_url) || $episodeSubs->isNotEmpty();
        $hasBothBoxes = $hasDownloadsBox && $hasSubtitlesBox;
    @endphp

    {{-- Download & Subtitles Section --}}
    @if($hasDownloadsBox || $hasSubtitlesBox)
        <section class="mt-10" id="downloads">
            <div class="{{ $hasBothBoxes ? 'mx-auto grid w-full max-w-5xl grid-cols-1 gap-6 md:grid-cols-2' : 'mx-auto w-full max-w-xl' }}">

                {{-- 1. Select Quality Box --}}
                @if($hasDownloadsBox)
                    <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-lg">
                        <h3 class="mb-5 flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-200">
                            <i class="fa-solid fa-hard-drive text-orange-500"></i> Select Quality &amp; Download
                        </h3>

                        <div class="space-y-3">
                            @foreach($quickDownloads as $dl)
                                <a href="{{ $dl['url'] }}"
                                   data-dl-notice
                                   @if($dl['direct']) data-dl-filename="{{ $dl['filename'] }}" @endif
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="group flex items-center justify-between rounded-lg bg-black/40 p-3 ring-1 ring-white/5 transition hover:bg-black/60 hover:ring-orange-500/50">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 flex-col items-center justify-center rounded bg-white/5 font-bold text-slate-300 transition group-hover:bg-orange-500/10 group-hover:text-orange-400">
                                            <span class="text-[10px] leading-none text-slate-500">{{ $dl['direct'] ? 'MP4' : 'WEB' }}</span>
                                            <span class="mt-0.5 text-sm">{{ $dl['badge'] }}</span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-200 transition group-hover:text-white">{{ $dl['label'] }}</div>
                                            <div class="mt-0.5 flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
                                                <span class="rounded bg-white/10 px-1.5 py-0.5 text-emerald-400">DOWNLOAD</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid h-10 w-10 place-items-center rounded-full bg-white/5 text-slate-400 transition group-hover:bg-orange-500 group-hover:text-white">
                                        <i class="fa-solid fa-download"></i>
                                    </div>
                                </a>
                            @endforeach

                            @if($movie->telegram_url)
                                <button type="button"
                                        data-tg-download
                                        data-tg-title="{{ $movie->title }}"
                                        data-tg-target="{{ $isTgExpired ? $waRequestUrl : $movie->telegram_url }}"
                                        data-tg-expired="{{ $isTgExpired ? '1' : '0' }}"
                                        class="group flex w-full items-center justify-between rounded-lg bg-black/40 p-3 text-left ring-1 ring-white/5 transition hover:bg-black/60 hover:ring-sky-500/50">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 flex-col items-center justify-center rounded bg-sky-500/10 font-bold text-sky-400 transition group-hover:bg-sky-500/20">
                                            <i class="fa-brands fa-telegram text-xl"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-200 transition group-hover:text-white">Telegram Download</div>
                                            <div class="mt-0.5 flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
                                                <span class="rounded bg-sky-500/10 px-1.5 py-0.5 text-sky-400">TELEGRAM</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid h-10 w-10 place-items-center rounded-full bg-white/5 text-slate-400 transition group-hover:bg-sky-500 group-hover:text-white">
                                        <i class="fa-solid fa-download"></i>
                                    </div>
                                </button>
                            @endif
                        </div>

                        @if($movie->watch_with_sub)
                            <div class="mt-4 rounded-xl border border-emerald-500/40 bg-emerald-500/15 p-4 text-center shadow-lg">
                                <p class="text-base font-extrabold leading-relaxed text-emerald-300 sm:text-lg">
                                    <i class="fa-solid fa-closed-captioning mr-1.5"></i>
                                    මේ චිත්‍රපටය සඳහා subtitle අවශ්‍ය නැහැ. Sub දාලා තියෙන්නේ, download කරලා බලන්න.
                                </p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- 2. Subtitles Box - Only shows if subtitle link is added --}}
                @if($hasSubtitlesBox)
                    <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-lg">
                        <h3 class="mb-5 flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-200">
                            <i class="fa-regular fa-closed-captioning text-orange-500"></i> Subtitles
                        </h3>

                        <div class="space-y-3">
                            @if($movie->subtitle_url)
                                <a href="{{ $movie->subtitle_url }}" download="{{ $safeFileBase }}-sinhala-sub.srt" target="_blank" rel="noopener noreferrer" class="group flex items-center justify-between rounded-lg bg-black/40 p-3 ring-1 ring-white/5 transition hover:bg-black/60 hover:ring-orange-500/50">
                                    <div class="flex items-center gap-4">
                                        <div class="grid h-12 w-12 place-items-center rounded bg-orange-500/10 text-xl text-orange-400 transition group-hover:bg-orange-500/20">
                                            <i class="fa-regular fa-file-lines"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-200 transition group-hover:text-white">Sinhala Subtitle</div>
                                            <div class="mt-0.5 text-[10px] font-semibold uppercase text-slate-500">BY AuraCinema</div>
                                        </div>
                                    </div>
                                    <div class="grid h-10 w-10 place-items-center rounded-full bg-white/5 text-slate-400 transition group-hover:bg-orange-500 group-hover:text-white">
                                        <i class="fa-solid fa-download"></i>
                                    </div>
                                </a>
                            @endif

                            {{-- Per-episode subtitles (series) --}}
                            @if($episodeSubs->isNotEmpty())
                                <div>
                                    <div class="mb-2 text-[10px] font-semibold uppercase text-slate-500">Episode subtitles</div>
                                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                                        @foreach($episodeSubs as $episode)
                                            <a href="{{ $episode->subtitle_url }}" target="_blank" rel="noopener"
                                               class="group flex items-center justify-center gap-1.5 rounded-md bg-black/40 py-2.5 text-xs font-semibold text-slate-200 ring-1 ring-white/5 transition hover:bg-orange-500 hover:text-white">
                                                <i class="fa-solid fa-download text-[10px] text-orange-400 group-hover:text-white"></i> Ep {{ $episode->number }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </section>
    @endif

    {{-- Download Notice Popup Modal ("Ads close කරලා download කරගන්න") --}}
    <dialog data-dl-modal aria-label="Download notice"
            class="fixed inset-0 m-auto w-[min(92vw,24rem)] overflow-hidden rounded-3xl border border-white/15 bg-zinc-950 p-0 text-white shadow-2xl shadow-black/80 backdrop:bg-black/80 backdrop:backdrop-blur-sm">
        <div class="relative p-6 text-center">
            <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-orange-500 via-amber-400 to-emerald-400"></div>
            <button type="button" data-dl-close aria-label="Close"
                    class="absolute right-3.5 top-3.5 grid h-8 w-8 place-items-center rounded-full bg-white/10 text-white transition hover:bg-orange-500">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <div class="mx-auto mb-3 mt-1 grid h-14 w-14 place-items-center rounded-2xl bg-orange-500/15 text-2xl text-orange-400 ring-1 ring-orange-500/30">
                <i class="fa-solid fa-download"></i>
            </div>

            <span class="mb-1 inline-block text-[11px] font-bold uppercase tracking-widest text-orange-400">
                AuraCinema Notice
            </span>

            <p class="mb-5 mt-2 text-lg font-extrabold leading-snug text-amber-300">
                Ads close කරලා download කරගන්න
            </p>

            <a href="#" target="_blank" rel="noopener noreferrer" data-dl-ok
               class="block w-full rounded-full bg-orange-500 py-3 text-sm font-extrabold uppercase tracking-wider text-white shadow-lg shadow-orange-500/25 transition hover:bg-orange-400">
                OK &middot; Download
            </a>
        </div>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dlBtns = document.querySelectorAll('[data-dl-notice]');
            const dlModal = document.querySelector('[data-dl-modal]');
            const dlClose = document.querySelector('[data-dl-close]');
            const dlOk = document.querySelector('[data-dl-ok]');

            if (!dlBtns.length || !dlModal || !dlOk) return;

            dlBtns.forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    dlOk.href = btn.href;
                    const fname = btn.dataset.dlFilename;
                    if (fname) {
                        dlOk.setAttribute('download', fname);
                    } else {
                        dlOk.removeAttribute('download');
                    }

                    if (typeof dlModal.showModal === 'function') {
                        dlModal.showModal();
                    } else {
                        dlModal.setAttribute('open', '');
                    }
                });
            });

            const closeDlModal = () => {
                if (typeof dlModal.close === 'function') {
                    dlModal.close();
                } else {
                    dlModal.removeAttribute('open');
                }
            };

            dlClose?.addEventListener('click', closeDlModal);
            dlOk?.addEventListener('click', () => setTimeout(closeDlModal, 150));
            dlModal.addEventListener('click', (e) => {
                if (e.target === dlModal) closeDlModal();
            });
        });
    </script>

    @php
        $hasAnyTelegram = $movie->telegram_url || $movie->episodes->contains(fn ($e) => filled($e->telegram_url));
    @endphp

    @if($hasAnyTelegram)
        <dialog data-tg-modal aria-label="{{ $movie->title }} Telegram notice"
                class="fixed inset-0 m-auto w-[min(92vw,24rem)] overflow-hidden rounded-3xl border border-white/15 bg-zinc-950 p-0 text-white shadow-2xl shadow-black/80 backdrop:bg-black/80 backdrop:backdrop-blur-sm">
            <div class="relative aspect-[3/4] w-full overflow-hidden bg-zinc-900">
                @if($movie->image)
                    <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-sky-900/40 to-zinc-950 text-4xl font-extrabold text-white/20">
                        <i class="fa-brands fa-telegram"></i>
                    </div>
                @endif

                {{-- Top Accent Bar & Close Button --}}
                <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-sky-400 via-cyan-400 to-orange-500"></div>
                <button type="button" data-tg-close aria-label="Close"
                        class="absolute right-3.5 top-4 z-10 grid h-9 w-9 place-items-center rounded-full bg-black/65 text-white ring-1 ring-white/20 backdrop-blur-md transition hover:bg-orange-500">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>

                {{-- Bottom Dark Gradient Overlay & Content --}}
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/85 to-transparent"></div>

                <div class="absolute inset-x-0 bottom-0 flex flex-col items-center p-6 text-center">
                    <span class="mb-1.5 inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-widest text-sky-400">
                        <i class="fa-brands fa-telegram"></i> AuraCinema Notice
                    </span>
                    <h3 data-tg-modal-title class="mb-2 line-clamp-1 text-xl font-extrabold uppercase tracking-tight text-white">
                        {{ $movie->title }}
                    </h3>
                    <p data-tg-msg class="mb-5 text-base font-bold leading-snug text-amber-300 drop-shadow">
                        Copyright ප්‍රශ්න නිසා සති දෙකකින් delete වේ .
                    </p>
                    <a href="#" target="_blank" rel="noopener" data-tg-ok
                       class="w-full rounded-full bg-white py-3 text-sm font-extrabold uppercase tracking-wider text-black shadow-lg transition hover:bg-orange-500 hover:text-white">
                        OK
                    </a>
                </div>
            </div>
        </dialog>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const tgBtns = document.querySelectorAll('[data-tg-download]');
                const tgModal = document.querySelector('[data-tg-modal]');
                const tgClose = document.querySelector('[data-tg-close]');
                const tgTitle = document.querySelector('[data-tg-modal-title]');
                const tgMsg = document.querySelector('[data-tg-msg]');
                const tgOk = document.querySelector('[data-tg-ok]');

                if (!tgBtns.length || !tgModal) return;

                tgBtns.forEach((tgBtn) => {
                    tgBtn.addEventListener('click', () => {
                        const isExpired = tgBtn.dataset.tgExpired === '1';
                        tgOk.href = tgBtn.dataset.tgTarget;
                        if (tgTitle && tgBtn.dataset.tgTitle) {
                            tgTitle.textContent = tgBtn.dataset.tgTitle;
                        }
                        tgMsg.textContent = isExpired
                            ? 'Copyright ප්‍රශ්න නිසා delete වී ඇත. කරුනාකර request කරන්න.'
                            : 'Copyright ප්‍රශ්න නිසා සති දෙකකින් delete වේ .';

                        if (typeof tgModal.showModal === 'function') {
                            tgModal.showModal();
                        } else {
                            tgModal.setAttribute('open', '');
                        }
                    });
                });

                const closeModal = () => {
                    if (typeof tgModal.close === 'function') {
                        tgModal.close();
                    } else {
                        tgModal.removeAttribute('open');
                    }
                };

                tgClose?.addEventListener('click', closeModal);
                tgOk?.addEventListener('click', () => setTimeout(closeModal, 150));
                tgModal.addEventListener('click', (e) => {
                    if (e.target === tgModal) closeModal();
                });
            });
        </script>
    @endif

    @if($defaultPlayerSrc)
        {{-- Subtitle Instruction Popup Modal (Zoomed & Clear Step-by-Step Guide) --}}
        <dialog data-sub-guide-modal aria-label="Sinhala Subtitle Guide"
                class="fixed inset-0 m-auto max-h-[92vh] w-[min(94vw,42rem)] overflow-y-auto on-dark rounded-3xl border border-white/15 bg-[#101016] p-0 text-white shadow-2xl shadow-black/95 backdrop:bg-black/85 backdrop:backdrop-blur-md">
            <div class="relative p-5 sm:p-7">
                {{-- Top Gradient Accent --}}
                <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-orange-500 via-amber-400 to-emerald-400"></div>

                {{-- Close Button --}}
                <button type="button" data-sub-guide-close aria-label="Close"
                        class="absolute right-4 top-4 z-10 grid h-9 w-9 place-items-center rounded-full bg-white/10 text-slate-200 ring-1 ring-white/15 transition hover:bg-orange-500 hover:text-white">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>

                {{-- Header Section --}}
                <div class="flex items-start gap-3.5 pr-10">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-orange-500/25 to-amber-500/10 text-xl text-orange-400 ring-1 ring-orange-500/40">
                        <i class="fa-solid fa-closed-captioning"></i>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-widest text-orange-400">
                            AuraCinema Subtitle Guide
                        </span>
                        <h3 class="mt-0.5 text-lg font-extrabold leading-snug text-white sm:text-xl">
                            සිංහල Subtitle දාගන්නේ කොහොමද?
                        </h3>
                        <p class="mt-1 text-xs font-medium leading-relaxed text-slate-300 sm:text-sm">
                            Video player එකේ පහළ දකුණු කොනේ ඇති <span class="font-bold text-amber-300">Settings (<i class="fa-solid fa-gear text-white"></i>)</span> icon එක ඔබලා <span class="font-bold text-emerald-400">Captions &rarr; Singhalese</span> තෝරන්න.
                        </p>
                    </div>
                </div>

                {{-- Zoomed Step Cards --}}
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Step 1 --}}
                    <div class="flex flex-col overflow-hidden rounded-2xl border border-orange-500/30 bg-zinc-900/90 shadow-lg">
                        <div class="flex items-center gap-2.5 border-b border-white/10 bg-white/[0.03] px-3.5 py-2.5">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-orange-500 text-xs font-extrabold text-white shadow">
                                1
                            </span>
                            <span class="text-xs font-bold text-slate-100 sm:text-sm">
                                <span class="text-orange-400">Settings (<i class="fa-solid fa-gear"></i>)</span> icon එක ඔබන්න
                            </span>
                        </div>
                        <div class="relative aspect-[16/10] w-full overflow-hidden bg-black">
                            <img src="{{ asset('images/sub-step-1.jpg') }}"
                                 alt="Step 1: Click Settings gear icon"
                                 class="h-full w-full origin-bottom-right scale-[1.75] object-cover">
                        </div>
                    </div>

                    {{-- Step 2 --}}
                    <div class="flex flex-col overflow-hidden rounded-2xl border border-emerald-500/30 bg-zinc-900/90 shadow-lg">
                        <div class="flex items-center gap-2.5 border-b border-white/10 bg-white/[0.03] px-3.5 py-2.5">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-emerald-500 text-xs font-extrabold text-zinc-950 shadow">
                                2
                            </span>
                            <span class="text-xs font-bold text-slate-100 sm:text-sm">
                                Captions &rarr; <span class="text-emerald-400">Singhalese</span> තෝරන්න
                            </span>
                        </div>
                        <div class="relative aspect-[16/10] w-full overflow-hidden bg-black">
                            <img src="{{ asset('images/sub-step-2.jpg') }}"
                                 alt="Step 2: Select Captions Singhalese"
                                 class="h-full w-full origin-bottom-right scale-[1.75] object-cover">
                        </div>
                    </div>
                </div>

                {{-- Helpful Note --}}
                <div class="mt-4 flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/[0.04] px-3.5 py-2.5 text-xs text-slate-300">
                    <i class="fa-solid fa-circle-info shrink-0 text-amber-400"></i>
                    <span>Full-screen කරලා බලද්දීත් මේ විදිහටම <strong>Settings (<i class="fa-solid fa-gear"></i>)</strong> icon එකෙන් සිංහල subtitle දාගන්න පුළුවන්.</span>
                </div>

                {{-- Action Button --}}
                <button type="button" data-sub-guide-close
                        class="mt-5 flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 py-3.5 text-sm font-extrabold tracking-wide text-white shadow-lg shadow-orange-500/25 transition hover:from-orange-400 hover:to-amber-400">
                    <i class="fa-solid fa-check"></i> හරි, තේරුණා · දැන් බලමු
                </button>
            </div>
        </dialog>

        <script>
            let auraShieldEnabled = false;
            let auraShowLoaderFn = null;
            let auraHideLoaderFn = null;
            let auraPlayClickTriggered = false;

            function sanitizeStreamtapeEmbedUrl(url) {
                if (!url) return url;
                return url.replace(
                    /(https?:\/\/(?:www\.)?(?:streamtape\.[a-z]+|stape\.[a-z]+|strcloud\.[a-z]+))\/v\//i,
                    '$1/e/'
                );
            }

            function applyPlayerAdShield(iframe) {
                if (!iframe) return;

                const rawSrc = iframe.getAttribute('src') || '';
                const cleanSrc = sanitizeStreamtapeEmbedUrl(rawSrc);

                iframe.setAttribute('referrerpolicy', 'no-referrer');

                if (auraShieldEnabled) {
                    const hadSandbox = iframe.hasAttribute('sandbox');
                    if (!hadSandbox) {
                        iframe.setAttribute(
                            'sandbox',
                            'allow-scripts allow-same-origin allow-forms allow-presentation'
                        );
                    }
                    if (cleanSrc && (!hadSandbox || rawSrc !== cleanSrc) && iframe.dataset.shieldReloaded !== cleanSrc) {
                        iframe.dataset.shieldReloaded = cleanSrc;
                        iframe.setAttribute('src', cleanSrc);
                    }
                } else {
                    const hadSandbox = iframe.hasAttribute('sandbox');
                    iframe.removeAttribute('sandbox');
                    delete iframe.dataset.shieldReloaded;
                    if (cleanSrc && (hadSandbox || rawSrc !== cleanSrc)) {
                        iframe.setAttribute('src', cleanSrc);
                    }
                }

                if (cleanSrc && cleanSrc !== 'about:blank' && iframe.dataset.loaderBoundSrc !== cleanSrc) {
                    iframe.dataset.loaderBoundSrc = cleanSrc;
                    auraPlayClickTriggered = false;
                    if (typeof auraShowLoaderFn === 'function') {
                        auraShowLoaderFn(iframe.getAttribute('title') || '', 2600);
                    }
                    if (typeof auraHideLoaderFn === 'function') {
                        iframe.addEventListener('load', auraHideLoaderFn, { once: true });
                    }
                }
            }

            function toggleAuraAdShield() {
                auraShieldEnabled = !auraShieldEnabled;
                const btn = document.getElementById('auraAdShieldBtn');
                const label = document.getElementById('auraAdShieldText');

                if (btn && label) {
                    if (auraShieldEnabled) {
                        label.textContent = 'Ad Shield: ON';
                        btn.classList.remove('border-amber-400/40', 'bg-amber-500/15', 'text-amber-300');
                        btn.classList.add('border-sky-400/40', 'bg-sky-500/15', 'text-sky-300');
                    } else {
                        label.textContent = 'Ad Shield: OFF';
                        btn.classList.remove('border-sky-400/40', 'bg-sky-500/15', 'text-sky-300');
                        btn.classList.add('border-amber-400/40', 'bg-amber-500/15', 'text-amber-300');
                    }
                }

                const embedContainer = document.querySelector('[data-embed]');
                if (embedContainer) {
                    embedContainer.querySelectorAll('iframe').forEach((iframe) => {
                        delete iframe.dataset.shieldReloaded;
                        delete iframe.dataset.loaderBoundSrc;
                        applyPlayerAdShield(iframe);
                    });
                }
            }

            document.addEventListener('DOMContentLoaded', () => {
                const embedContainer = document.querySelector('[data-embed]');
                const playerLoader = document.getElementById('auraPlayerLoader');
                const playerLoaderTitle = document.getElementById('auraPlayerLoaderTitle');
                const playerFunMsg = document.getElementById('auraPlayerFunMsg');
                const funCaptions = [
                    '🍿 Popcorn ලෑස්ති කරගන්න...',
                    '🎬 Cinema screen එක සූදානම් වෙමින් පවතී...',
                    '✨ ඔන්න දැන් පටන් ගන්නවා...'
                ];
                let loaderFallbackTimer = null;
                let captionInterval = null;
                const baseTitle = @json($movie->title);
                let currentTitle = baseTitle;

                auraShowLoaderFn = (titleText, durationMs = 4200) => {
                    if (!playerLoader) return;
                    if (playerLoaderTitle) {
                        playerLoaderTitle.textContent = titleText || currentTitle;
                    }

                    let msgIdx = 0;
                    if (playerFunMsg) playerFunMsg.textContent = funCaptions[0];
                    clearInterval(captionInterval);
                    captionInterval = setInterval(() => {
                        msgIdx = (msgIdx + 1) % funCaptions.length;
                        if (playerFunMsg) playerFunMsg.textContent = funCaptions[msgIdx];
                    }, 1300);

                    clearTimeout(loaderFallbackTimer);
                    playerLoader.classList.remove('hidden', 'opacity-0');
                    playerLoader.classList.add('flex', 'opacity-100');

                    loaderFallbackTimer = setTimeout(() => {
                        if (typeof auraHideLoaderFn === 'function') auraHideLoaderFn();
                    }, durationMs);
                };

                auraHideLoaderFn = () => {
                    if (!playerLoader || playerLoader.classList.contains('hidden')) return;
                    clearTimeout(loaderFallbackTimer);
                    clearInterval(captionInterval);
                    playerLoader.classList.remove('opacity-100');
                    playerLoader.classList.add('opacity-0');
                    setTimeout(() => {
                        playerLoader.classList.add('hidden');
                        playerLoader.classList.remove('flex');
                    }, 300);
                };

                // Player eka athule thiyena Play button eka click kalama (00:00 / 00:00 buffer weddi) animation eka pennima
                window.addEventListener('blur', () => {
                    setTimeout(() => {
                        if (!embedContainer || auraPlayClickTriggered) return;
                        const activeEl = document.activeElement;
                        if (activeEl && activeEl.tagName === 'IFRAME' && embedContainer.contains(activeEl)) {
                            auraPlayClickTriggered = true;
                            if (typeof auraShowLoaderFn === 'function') {
                                auraShowLoaderFn(currentTitle, 4200);
                            }
                        }
                    }, 50);
                });

                // 1. [data-embed] athule load wena Streamtape player iframe ekata Ad Shield + Cute Loader bind kirima
                if (embedContainer) {
                    const attachShield = (iframe) => {
                        applyPlayerAdShield(iframe);
                        const attrObserver = new MutationObserver(() => applyPlayerAdShield(iframe));
                        attrObserver.observe(iframe, { attributes: true, attributeFilter: ['src'] });
                    };

                    embedContainer.querySelectorAll('iframe').forEach(attachShield);

                    const embedObserver = new MutationObserver((mutations) => {
                        mutations.forEach((m) => {
                            m.addedNodes.forEach((node) => {
                                if (node.nodeType !== 1) return;
                                if (node.tagName === 'IFRAME') {
                                    attachShield(node);
                                } else if (node.querySelectorAll) {
                                    node.querySelectorAll('iframe').forEach(attachShield);
                                }
                            });
                        });
                    });
                    embedObserver.observe(embedContainer, { childList: true, subtree: true });
                }

                // 2. "Subtitle දාගන්න හැටි" button eka ebuwama witharak guide popup eka open kirima
                const subModal = document.querySelector('[data-sub-guide-modal]');
                const closeBtns = document.querySelectorAll('[data-sub-guide-close]');
                const manualOpenBtn = document.querySelector('[data-sub-guide-open]');

                if (subModal) {
                    const openSubGuide = () => {
                        if (typeof subModal.showModal === 'function') {
                            if (!subModal.open) subModal.showModal();
                        } else {
                            subModal.setAttribute('open', '');
                        }
                    };

                    const closeSubGuide = () => {
                        if (typeof subModal.close === 'function') {
                            subModal.close();
                        } else {
                            subModal.removeAttribute('open');
                        }
                    };

                    manualOpenBtn?.addEventListener('click', openSubGuide);
                    closeBtns.forEach((btn) => btn.addEventListener('click', closeSubGuide));
                    subModal.addEventListener('click', (e) => {
                        if (e.target === subModal) closeSubGuide();
                    });
                }

                // 3. Watch Now, Episodes & Continue Watching Two-Way Sync (Website -> Mobile App)
                let currentEpisodeId = 0;
                let isPlayingStarted = false;
                let watchedSeconds = {{ (int) request()->query('t', 0) }};

                document.querySelector('[data-embed-load]')?.addEventListener('click', (e) => {
                    isPlayingStarted = true;
                    auraPlayClickTriggered = false;
                    currentTitle = e.currentTarget.dataset.embedTitle || baseTitle;
                    if (typeof auraShowLoaderFn === 'function') {
                        auraShowLoaderFn(currentTitle, 2600);
                    }
                });

                document.querySelectorAll('[data-episode-btn]').forEach((btn, idx) => {
                    btn.addEventListener('click', () => {
                        isPlayingStarted = true;
                        auraPlayClickTriggered = false;
                        const epNum = parseInt(btn.dataset.episode || '', 10);
                        currentEpisodeId = !Number.isNaN(epNum) ? epNum : (idx + 1);
                        currentTitle = btn.dataset.embedTitle || `${baseTitle} - Episode ${currentEpisodeId}`;
                        watchedSeconds = 0;
                        if (typeof auraShowLoaderFn === 'function') {
                            auraShowLoaderFn(currentTitle, 2600);
                        }
                    });
                });

                @if(auth()->check() && \Illuminate\Support\Facades\Route::has('user.history.store'))
                const historyEndpoint = "{{ route('user.history.store') }}";
                const csrfToken = "{{ csrf_token() }}";

                setInterval(() => {
                    const activeFrame = embedContainer ? embedContainer.querySelector('iframe[src*="http"]') : null;
                    if (!document.hidden && (isPlayingStarted || activeFrame)) {
                        watchedSeconds += 15;
                        fetch(historyEndpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                movie_id: {{ (int) $movie->id }},
                                episode_id: currentEpisodeId,
                                title: currentTitle,
                                poster_url: @json($movie->poster_url ?? ''),
                                type: @json($movie->type ?? 'movie'),
                                position_seconds: watchedSeconds,
                                duration_seconds: 0
                            })
                        }).catch(() => {});
                    }
                }, 15000);
                @endif
            });
        </script>
    @endif
@endsection