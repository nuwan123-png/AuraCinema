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

            <div class="flex shrink-0 items-center gap-2">
                @if(request()->cookie('aura_admin'))
                    <div class="hidden shrink-0 gap-2 lg:flex">
                        <a href="{{ route('movies.incomplete') }}" class="btn-ghost !px-4 !py-2">Incomplete</a>
                        <a href="{{ route('requests.index') }}" class="btn-ghost !px-4 !py-2">Requests</a>
                        <a href="{{ route('admin.stats') }}" class="btn-ghost !px-4 !py-2">Stats</a>
                        <a href="{{ route('movies.create') }}" class="btn-aura !px-4 !py-2">+ Add movie</a>
                    </div>
                @endif

                {{-- User Account / Profile Glass Button (Synced with Mobile App) --}}
                @auth
                    <div class="relative">
                        <button type="button" onclick="toggleWebProfileMenu(event)"
                                class="inline-flex items-center gap-2 rounded-full border border-sky-400/40 bg-[#131B2E]/85 py-1 pl-1.5 pr-3 text-xs font-bold text-white shadow-lg shadow-blue-950/40 backdrop-blur-xl transition hover:border-sky-400 hover:bg-blue-600/25">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-sky-400 text-xs font-extrabold text-white">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <span class="hidden max-w-[95px] truncate sm:inline">{{ auth()->user()->name }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-sky-300"></i>
                        </button>

                        <div id="webProfileDropdown"
                             class="hidden absolute right-0 top-full z-[70] mt-2.5 w-56 overflow-hidden rounded-2xl border border-white/10 bg-[#131B2E]/95 p-2 shadow-2xl shadow-black/90 backdrop-blur-2xl">
                            <div class="mb-1.5 border-b border-white/10 px-3 py-2">
                                <div class="truncate text-xs font-extrabold text-white">{{ auth()->user()->name }}</div>
                                <div class="truncate text-[11px] text-slate-400">{{ auth()->user()->email }}</div>
                            </div>

                            <a href="{{ route('user.profile') }}"
                               class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-200 transition hover:bg-sky-500/15 hover:text-white">
                                <i class="fa-solid fa-user text-sky-400"></i>
                                <span>My Profile &amp; History</span>
                            </a>

                            <form method="POST" action="{{ route('user.logout') }}" class="mt-0.5">
                                @csrf
                                <button type="submit"
                                        class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-left text-xs font-semibold text-rose-400 transition hover:bg-rose-500/15 hover:text-rose-300">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('user.auth') }}"
                       class="inline-flex items-center gap-1.5 rounded-full border border-sky-400/40 bg-blue-600/20 px-3.5 py-2 text-xs font-bold text-white backdrop-blur-xl transition hover:border-sky-400 hover:bg-blue-600">
                        <i class="fa-solid fa-circle-user text-sm text-sky-400"></i>
                        <span>Sign In</span>
                    </a>
                @endauth
            </div>
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

    <!-- Compact Floating Subtitle Notice (Bottom-Right, Non-Fullscreen) -->
    <div id="auraWelcomePopup"
         class="fixed inset-x-3 bottom-3 z-[70] hidden overflow-hidden rounded-2xl border border-orange-500/35 bg-[#12121a]/95 p-3.5 text-slate-200 shadow-2xl shadow-black/90 backdrop-blur-xl sm:inset-x-auto sm:bottom-5 sm:right-5 sm:w-[22.5rem]">
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-orange-500 via-amber-400 to-emerald-400"></div>

        <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-orange-500 animate-pulse"></span>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-orange-400">AuraCinema දැනුම්දීමයි</span>
            </div>
            <button type="button" onclick="closeAuraPopup()" aria-label="Close"
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs text-slate-300 transition hover:bg-orange-500 hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="mt-2.5 space-y-2 text-xs leading-relaxed">
            <div class="flex items-start gap-2 rounded-xl border border-white/10 bg-white/[0.04] p-2.5">
                <i class="fa-solid fa-closed-captioning mt-0.5 text-orange-400"></i>
                <p>ඔයාලට <span class="font-bold text-amber-300">Watch now</span> කියන එකෙන් සිංහල subtitle එක්කම බලන්න පුළුවන්.</p>
            </div>

            <div class="flex items-start gap-2 rounded-xl border border-white/10 bg-white/[0.04] p-2.5">
                <i class="fa-solid fa-gear mt-0.5 text-emerald-400"></i>
                <p>Watch now player එකේ <span class="font-bold text-white">Settings</span> වලට ගිහින් <span class="font-bold text-emerald-400">Captions &rarr; Singhalese</span> දාගන්න.</p>
            </div>
        </div>

        <div class="mt-3 flex items-center gap-2">
            <button type="button" onclick="goToWatchWithSub()"
                    class="flex-1 rounded-xl bg-emerald-500 py-2 text-center text-xs font-bold text-zinc-950 transition hover:bg-emerald-400">
                <i class="fa-solid fa-closed-captioning mr-1"></i> Watch with sub
            </button>
            <button type="button" onclick="closeAuraPopup()"
                    class="flex-1 rounded-xl bg-orange-500 py-2 text-center text-xs font-bold text-white transition hover:bg-orange-400">
                හරි, තේරුණා
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('auraWelcomePopup');
            if (popup && !sessionStorage.getItem('aura_welcome_seen')) {
                popup.classList.remove('hidden');
            }
        });

        function closeAuraPopup() {
            const popup = document.getElementById('auraWelcomePopup');
            if (popup) {
                popup.classList.add('hidden');
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

        function toggleWebProfileMenu(event) {
            event.stopPropagation();
            const menu = document.getElementById('webProfileDropdown');
            if (menu) menu.classList.toggle('hidden');
        }

        document.addEventListener('click', function () {
            const menu = document.getElementById('webProfileDropdown');
            if (menu) menu.classList.add('hidden');
        });
    </script>

    @stack('modals')
</body>
</html>