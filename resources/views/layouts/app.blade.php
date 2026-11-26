<!DOCTYPE html>
<html lang="en" class="bg-[#0b0b0f]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b0b0f">
    @php($siteUrl = rtrim((string) config('auracinema.site_url'), '/'))
    @php($defaultDescription = 'AuraCinema - browse movies, watch trailers and find full movies.')
    <title>@yield('title', 'AuraCinema') | Movies &amp; Trailers</title>
    <meta name="description" content="@yield('description', $defaultDescription)">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    @if(config('auracinema.google_verification'))
        <meta name="google-site-verification" content="{{ config('auracinema.google_verification') }}">
    @endif
    <link rel="canonical" href="{{ $siteUrl.request()->getPathInfo() }}">
    <meta property="og:site_name" content="AuraCinema">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', 'AuraCinema')">
    <meta property="og:description" content="@yield('description', $defaultDescription)">
    <meta property="og:url" content="{{ $siteUrl.request()->getPathInfo() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    @stack('jsonld')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        ['https://streamtape.com', 'https://tapecontent.net'].forEach(function (url) {
            var l = document.createElement('link');
            l.rel = 'preconnect';
            l.href = url;
            l.crossOrigin = 'anonymous';
            document.head.appendChild(l);
        });
    </script>
</head>

<body class="flex min-h-screen flex-col font-sans text-slate-100 antialiased selection:bg-orange-500 selection:text-white">

    {{-- Nav helpers --}}
    @php($navType = request('type'))
    @php($navLink = fn (bool $active) => 'py-1.5 transition '.($active ? 'text-orange-400' : 'hover:text-orange-300'))
    @php($keepTypeLang = array_filter(['type' => request('type'), 'language' => request('language')]))
    @php($keepTypeGenreLang = array_filter(['type' => request('type'), 'genre' => request('genre'), 'language' => request('language')]))

    <header class="z-50 border-b border-white/5 bg-[#0b0b0f]/80 backdrop-blur-xl md:sticky md:top-0">
        <div class="relative mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2" aria-label="AuraCinema home">
                <span class="h-3 w-3 rounded-full bg-gradient-to-br from-orange-400 to-amber-500 shadow-[0_0_14px_rgba(249,115,22,0.9)]"></span>
                <span class="text-xl font-extrabold tracking-tight">Aura<span class="bg-gradient-to-r from-orange-400 to-amber-300 bg-clip-text text-transparent">Cinema</span></span>
            </a>

            <form action="{{ route('movies.index') }}" method="GET" role="search" data-live-search data-live-url="{{ route('search.live') }}" class="w-full max-w-sm sm:relative">
                <label for="q" class="sr-only">Search movies</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-500"></i>
                    <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Search movies" autocomplete="off"
                           role="combobox" aria-expanded="false" aria-controls="live-results" aria-autocomplete="list"
                           class="w-full rounded-full border border-white/10 bg-white/5 py-2.5 pl-9 pr-4 text-sm text-white placeholder-slate-500 transition focus:border-orange-400/70 focus:bg-white/10 focus:outline-none focus:ring-4 focus:ring-orange-500/10">
                </div>

                {{-- Filled by resources/js/app.js while you type --}}
                <div id="live-results" data-live-results role="listbox" hidden
                     class="glass-panel absolute inset-x-3 top-full z-[60] -mt-1 overflow-hidden sm:inset-x-auto sm:left-0 sm:mt-2 sm:w-[26rem]"></div>
            </form>

            @if(request()->cookie('aura_admin'))
                <div class="hidden shrink-0 gap-2 sm:flex">
                    <a href="{{ route('movies.incomplete') }}" class="btn-ghost !px-4 !py-2">Incomplete</a>
                    <a href="{{ route('requests.index') }}" class="btn-ghost !px-4 !py-2">Requests</a>
                    <a href="{{ route('admin.stats') }}" class="btn-ghost !px-4 !py-2">Stats</a>
                    <a href="{{ route('movies.create') }}" class="btn-aura !px-4 !py-2">+ Add movie</a>
                </div>
            @endif
        </div>

        {{-- Category bar --}}
        <nav class="border-t border-white/5" aria-label="Primary">
            <div class="relative mx-auto flex max-w-7xl flex-wrap items-center gap-x-5 gap-y-0 px-4 py-1 text-[13px] font-semibold uppercase tracking-wide text-slate-300 sm:px-6">

                {{-- Genre Dropdown --}}
                <details data-menu class="sm:relative">
                    <summary class="flex cursor-pointer list-none items-center gap-1 py-1.5 hover:text-white [&::-webkit-details-marker]:hidden {{ request('genre') ? 'text-orange-400' : '' }}">
                        Genre <i class="fa-solid fa-chevron-down text-[9px]"></i>
                    </summary>
                    <div class="absolute inset-x-4 top-full z-50 mt-2 grid max-h-72 grid-cols-3 gap-1 overflow-y-auto overscroll-contain glass-panel p-2 text-xs normal-case sm:inset-x-auto sm:left-0 sm:w-60 sm:grid-cols-2">
                        @foreach($globalNavGenres as $g)
                            <a href="{{ route('movies.index', array_merge($keepTypeLang, ['genre' => $g])) }}" class="menu-item {{ request('genre') === $g ? 'text-orange-400' : '' }}">{{ $g }}</a>
                        @endforeach
                    </div>
                </details>

                {{-- Type Links --}}
                @foreach($globalNavTypes as $navKey => $navLabel)
                    <a href="{{ route('movies.index', array_filter(['type' => $navKey, 'language' => request('language')])) }}" class="{{ $navLink($navType === $navKey) }}">
                        {{ $navLabel }}
                    </a>
                @endforeach

                <a href="{{ route('suggestions') }}" class="{{ $navLink(request()->routeIs('suggestions')) }}">Suggestion</a>

                {{-- Year Dropdown (All years 2027 down to 1990) --}}
                <details data-menu class="sm:relative">
                    <summary class="flex cursor-pointer list-none items-center gap-1 py-1.5 hover:text-white [&::-webkit-details-marker]:hidden {{ request('year') ? 'text-orange-400' : '' }}">
                        Year <i class="fa-solid fa-chevron-down text-[9px]"></i>
                    </summary>
                    <div class="absolute inset-x-4 top-full z-50 mt-2 glass-panel grid max-h-80 grid-cols-4 gap-1 overflow-y-auto overscroll-contain p-2 text-xs normal-case sm:inset-x-auto sm:left-0 sm:w-56 sm:grid-cols-3">
                        @if(request('year'))
                            <a href="{{ route('movies.index', $keepTypeGenreLang) }}" class="menu-item col-span-full text-center text-orange-400">All Years</a>
                        @endif
                        @php($navYears = range((int) date('Y') + 1, 1990))
                        @foreach($navYears as $y)
                            <a href="{{ route('movies.index', array_merge($keepTypeGenreLang, ['year' => $y])) }}" class="menu-item text-center {{ (int) request('year') === $y ? 'text-orange-400' : '' }}">{{ $y }}</a>
                        @endforeach
                    </div>
                </details>

                @php($waRequest = 'https://wa.me/'.preg_replace('/\D/', '', (string) config('auracinema.whatsapp_number')).'?text='.rawurlencode("Hi AuraCinema, I'd like to request a movie: "))
                <a href="{{ $waRequest }}" target="_blank" rel="noopener" class="{{ $navLink(false) }}"><i class="fa-brands fa-whatsapp text-emerald-400"></i> Request</a>
            </div>
        </nav>
    </header>

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6">
        @if(session('success'))
            <div class="mb-6 flex items-center gap-2 rounded-md border border-emerald-500/30 bg-emerald-500/10 p-3 text-sm text-emerald-300" role="status">
                <i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-md border border-rose-500/30 bg-rose-500/10 p-3 text-sm text-rose-300" role="alert">
                <p class="mb-1 font-semibold">Please fix the following:</p>
                <ul class="list-disc space-y-0.5 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="mt-10 border-t border-white/5 bg-gradient-to-b from-transparent to-black/40 py-10 text-center text-xs text-slate-500">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-extrabold text-white">
            <span class="h-2.5 w-2.5 rounded-full bg-gradient-to-br from-orange-400 to-amber-500 shadow-[0_0_12px_rgba(249,115,22,0.9)]"></span>
            <span>Aura<span class="bg-gradient-to-r from-orange-400 to-amber-300 bg-clip-text text-transparent">Cinema</span></span>
        </a>
        <p class="mt-3">&copy; {{ date('Y') }} AuraCinema. Built with Laravel.</p>
        <p class="mt-2"><a href="{{ route('movies.create') }}" class="transition hover:text-orange-300">Admin</a></p>
    </footer>

    <!-- AuraCinema Welcome Popup -->
    <div id="auraWelcomePopup" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-orange-500/30 bg-[#121218] shadow-2xl">
            <button type="button" onclick="closeAuraPopup()" aria-label="Close" class="absolute right-3 top-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-black/70 text-slate-300 hover:bg-orange-500 hover:text-white transition">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="relative h-44 w-full bg-zinc-900">
                <img src="https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=800&q=80"
                     alt="AuraCinema Notice"
                     class="h-full w-full object-cover opacity-80">
                <div class="absolute inset-0 bg-gradient-to-t from-[#121218] via-[#121218]/40 to-transparent"></div>
                <div class="absolute bottom-3 left-4 flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-orange-500 animate-pulse"></span>
                    <span class="text-xs font-bold tracking-wider text-orange-400 uppercase">AuraCinema දැනුම්දීමයි</span>
                </div>
            </div>

            <div class="p-5 space-y-3 text-sm text-slate-200 leading-relaxed">
                <div class="flex items-start gap-2.5 rounded-xl bg-white/5 p-3 border border-white/10">
                    <span class="text-orange-400 mt-0.5"><i class="fa-solid fa-desktop"></i></span>
                    <p>Desktop භාවිතයෙන් subtitles සහ movies download කිරීමේ පහසුකම ඇත.</p>
                </div>

                <div class="flex items-start gap-2.5 rounded-xl bg-white/5 p-3 border border-white/10">
                    <span class="text-orange-400 mt-0.5"><i class="fa-solid fa-film"></i></span>
                    <p>
                        Online movies බලන්න පුළුවන්. Sub එක්කම online බලන්න පුලුවන් දැනට දාලා තියන ඒවා විතරයි.
                        <span class="mt-1 inline-block rounded bg-orange-500/20 px-2 py-0.5 text-xs font-semibold text-orange-300 border border-orange-500/30">
                            Online watch with sub
                        </span>
                    </p>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row gap-2.5">
                    <button type="button" onclick="goToWatchWithSub()" class="w-full rounded-xl bg-emerald-500 py-2.5 text-center text-xs sm:text-sm font-bold text-zinc-950 hover:bg-emerald-400 transition">
                        <i class="fa-solid fa-closed-captioning mr-1"></i> Watch with sub
                    </button>
                    <button type="button" onclick="closeAuraPopup()" class="w-full rounded-xl bg-orange-500 py-2.5 text-center text-xs sm:text-sm font-bold text-white hover:bg-orange-400 transition">
                        හරි, තේරුණා
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('auraWelcomePopup');
            if (popup && !sessionStorage.getItem('aura_welcome_seen')) {
                popup.classList.remove('hidden');
                popup.classList.add('flex');
            }
        });

        function closeAuraPopup() {
            const popup = document.getElementById('auraWelcomePopup');
            if (popup) {
                popup.classList.add('hidden');
                popup.classList.remove('flex');
                sessionStorage.setItem('aura_welcome_seen', '1');
            }
        }

        function goToWatchWithSub() {
            closeAuraPopup();
            const section = document.getElementById('watch-with-sub-section');
            if (section) {
                section.scrollIntoView({ behavior: 'smooth' });
            } else {
                window.location.href = "{{ route('home') }}#watch-with-sub-section";
            }
        }
    </script>

    @stack('modals')
</body>
</html>