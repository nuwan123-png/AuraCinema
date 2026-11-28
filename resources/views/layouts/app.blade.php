<!DOCTYPE html>
<html lang="en" data-theme="dark" data-sb="expanded">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b0e17">
    {{-- Runs before first paint: restores the saved theme / sidebar state so the page never flashes the wrong colours. --}}
    <script>
        (function () {
            try {
                var r = document.documentElement;
                var t = localStorage.getItem('aura_theme');
                if (t === 'light' || t === 'dark') r.setAttribute('data-theme', t);
                if (localStorage.getItem('aura_sidebar') === 'collapsed') r.setAttribute('data-sb', 'collapsed');
            } catch (e) {}
        })();
    </script>
    @php
        $siteUrl = rtrim((string) config('auracinema.site_url'), '/');
        $defaultDescription = 'AuraCinema - browse movies, watch trailers and find full movies.';
    @endphp
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

<body class="min-h-screen font-sans text-slate-100 antialiased selection:bg-orange-500 selection:text-white">

    @php
        $navType = request('type');
        $keepTypeLang = array_filter(['type' => request('type'), 'language' => request('language')]);
        $keepTypeGenreLang = array_filter(['type' => request('type'), 'genre' => request('genre'), 'language' => request('language')]);
        $sbItem = fn (bool $active) => 'sb-item'.($active ? ' is-active' : '');
        $sbTypeIcons = ['movie' => 'fa-clapperboard', 'series' => 'fa-tv', 'anime' => 'fa-wand-magic-sparkles'];
        $waRequest = 'https://wa.me/'.preg_replace('/\D/', '', (string) config('auracinema.whatsapp_number')).'?text='.rawurlencode("Hi AuraCinema, I'd like to request a movie: ");
        $isAdmin = (bool) request()->cookie('aura_admin');
    @endphp

    {{-- Phone top bar: opens the sidebar as a slide-out drawer. Hidden on tablets and desktops. --}}
    <header class="glass sticky top-0 z-40 flex items-center justify-between gap-3 border-x-0 border-t-0 px-4 py-2.5 md:hidden">
        <button type="button" data-sb-open aria-label="Open menu" class="sb-icon-btn"><i class="fa-solid fa-bars"></i></button>
        <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="AuraCinema home">
            <span class="h-3 w-3 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 shadow-[0_0_14px_var(--color-orange-500)]"></span>
            <span class="text-lg font-extrabold tracking-tight">Aura<span class="text-orange-400">Cinema</span></span>
        </a>
        <div class="flex items-center gap-2">
            <button type="button" data-sb-open data-sb-focus-search aria-label="Search" class="sb-icon-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
            <button type="button" data-theme-toggle aria-label="Switch light / dark theme" class="sb-icon-btn"><i class="fa-solid fa-circle-half-stroke"></i></button>
        </div>
    </header>

    <div id="sbOverlay" data-sb-close class="fixed inset-0 z-[72] hidden bg-black/60 md:hidden"></div>

    {{-- ============ Left sidebar ============ --}}
    <aside id="sidebar" class="aura-sidebar glass" aria-label="Main menu">
        <div class="sb-head flex items-center justify-between gap-2 px-3 pt-4">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2.5" aria-label="AuraCinema home">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 text-lg font-extrabold text-white shadow-lg shadow-orange-500/30">A</span>
                <span data-sb-label class="truncate text-lg font-extrabold tracking-tight">Aura<span class="text-orange-400">Cinema</span></span>
            </a>
            <button type="button" data-sb-collapse aria-label="Collapse or expand menu" class="sb-icon-btn hidden md:grid"><i class="fa-solid fa-angles-left" data-sb-collapse-icon></i></button>
            <button type="button" data-sb-close aria-label="Close menu" class="sb-icon-btn md:hidden"><i class="fa-solid fa-xmark"></i></button>
        </div>

        {{-- Live search (script in resources/js/app.js looks for [data-live-search], input[name=q], [data-live-results]) --}}
        <form action="{{ route('movies.index') }}" method="GET" role="search" data-live-search data-live-url="{{ route('search.live') }}" class="relative px-3 pt-4">
            <label for="q" class="sr-only">Search movies</label>
            <div data-sb-search-field class="relative">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-500"></i>
                <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Search movies" autocomplete="off"
                       role="combobox" aria-expanded="false" aria-controls="live-results" aria-autocomplete="list"
                       class="w-full rounded-2xl border border-white/10 bg-white/5 py-2.5 pl-9 pr-3 text-sm text-white placeholder-slate-500 transition focus:border-orange-400/70 focus:bg-white/10 focus:outline-none focus:ring-4 focus:ring-orange-500/15">
            </div>
            <button type="button" data-sb-search-toggle aria-label="Search" title="Search" class="sb-item hidden !justify-center"><i class="fa-solid fa-magnifying-glass w-5 text-center"></i></button>
            <div id="live-results" data-live-results role="listbox" hidden
                 class="glass-panel absolute left-3 right-3 top-full z-[80] mt-2 max-h-[70vh] overflow-y-auto md:left-full md:right-auto md:top-4 md:ml-4 md:mt-0 md:w-[26rem]"></div>
        </form>

        <nav class="sb-nav mt-4 flex-1 space-y-1 px-3 pb-3" aria-label="Primary">
            <a href="{{ route('home') }}" title="Home" class="{{ $sbItem(request()->routeIs('home') && ! $navType) }}">
                <i class="fa-solid fa-house w-5 text-center"></i><span data-sb-label>Home</span>
            </a>

            @foreach($globalNavTypes as $navKey => $navLabel)
                <a href="{{ route('movies.index', array_filter(['type' => $navKey, 'language' => request('language')])) }}" title="{{ $navLabel }}" class="{{ $sbItem($navType === $navKey) }}">
                    <i class="fa-solid {{ $sbTypeIcons[$navKey] ?? 'fa-film' }} w-5 text-center"></i><span data-sb-label>{{ $navLabel }}</span>
                </a>
            @endforeach

            <a href="{{ route('suggestions') }}" title="Suggestion" class="{{ $sbItem(request()->routeIs('suggestions')) }}">
                <i class="fa-solid fa-lightbulb w-5 text-center"></i><span data-sb-label>Suggestion</span>
            </a>

            {{-- Genre --}}
            <details data-menu class="sb-menu">
                <summary class="{{ $sbItem((bool) request('genre')) }} cursor-pointer list-none [&::-webkit-details-marker]:hidden" title="Genre">
                    <i class="fa-solid fa-masks-theater w-5 text-center"></i><span data-sb-label>Genre</span>
                    <i data-sb-label class="fa-solid fa-chevron-down ml-auto text-[10px]"></i>
                </summary>
                <div class="sb-panel">
                    @foreach($globalNavGenres as $g)
                        <a href="{{ route('movies.index', array_merge($keepTypeLang, ['genre' => $g])) }}" class="menu-item {{ request('genre') === $g ? 'text-orange-400' : '' }}">{{ $g }}</a>
                    @endforeach
                </div>
            </details>

            {{-- Year --}}
            <details data-menu class="sb-menu">
                <summary class="{{ $sbItem((bool) request('year')) }} cursor-pointer list-none [&::-webkit-details-marker]:hidden" title="Year">
                    <i class="fa-solid fa-calendar-days w-5 text-center"></i><span data-sb-label>Year</span>
                    <i data-sb-label class="fa-solid fa-chevron-down ml-auto text-[10px]"></i>
                </summary>
                <div class="sb-panel sb-panel-years">
                    @if(request('year'))
                        <a href="{{ route('movies.index', $keepTypeGenreLang) }}" class="menu-item col-span-full text-center text-orange-400">All Years</a>
                    @endif
                    @foreach(range((int) date('Y') + 1, 1990) as $y)
                        <a href="{{ route('movies.index', array_merge($keepTypeGenreLang, ['year' => $y])) }}" class="menu-item text-center {{ (int) request('year') === $y ? 'text-orange-400' : '' }}">{{ $y }}</a>
                    @endforeach
                </div>
            </details>

            <a href="{{ $waRequest }}" target="_blank" rel="noopener" title="Request a movie" class="sb-item">
                <i class="fa-brands fa-whatsapp w-5 text-center text-emerald-400"></i><span data-sb-label>Request</span>
            </a>

            @if($isAdmin)
                <p data-sb-label class="px-3 pb-1 pt-4 text-[10px] font-extrabold uppercase tracking-widest text-slate-500">Admin</p>
                <div data-sb-admin-rule class="mx-2 my-3 hidden border-t border-white/10"></div>
                <a href="{{ route('movies.incomplete') }}" title="Incomplete" class="{{ $sbItem(request()->routeIs('movies.incomplete')) }}"><i class="fa-solid fa-triangle-exclamation w-5 text-center"></i><span data-sb-label>Incomplete</span></a>
                <a href="{{ route('requests.index') }}" title="Requests" class="{{ $sbItem(request()->routeIs('requests.index')) }}"><i class="fa-solid fa-inbox w-5 text-center"></i><span data-sb-label>Requests</span></a>
                <a href="{{ route('admin.stats') }}" title="Stats" class="{{ $sbItem(request()->routeIs('admin.stats')) }}"><i class="fa-solid fa-chart-line w-5 text-center"></i><span data-sb-label>Stats</span></a>
                <a href="{{ route('movies.create') }}" title="Add movie" class="btn-aura mt-2 w-full !rounded-2xl !py-2.5"><i class="fa-solid fa-plus"></i><span data-sb-label>Add movie</span></a>
                @php($isAppUpdating = \App\Models\AppSetting::getValue('app_update_active', '0') === '1')
<form method="POST" action="{{ route('admin.app-update.toggle') }}" class="inline-flex">
    @csrf
    <button type="submit"
            class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-2 text-xs font-bold transition {{ $isAppUpdating ? 'border-amber-400 bg-amber-500/20 text-amber-300 animate-pulse' : 'border-white/15 bg-white/5 text-slate-300 hover:border-sky-400' }}">
        <i class="fa-solid fa-rotate text-[11px]"></i>
        <span>App Update: {{ $isAppUpdating ? 'ON' : 'OFF' }}</span>
    </button>
</form>
            @endif
        </nav>

        <div class="relative space-y-1 border-t border-white/10 p-3">
            <button type="button" data-theme-toggle title="Switch light / dark theme" class="sb-item w-full">
                <i class="fa-solid fa-moon w-5 text-center" data-theme-icon-dark></i>
                <i class="fa-solid fa-sun w-5 text-center" data-theme-icon-light></i>
                <span data-sb-label data-theme-label>Theme</span>
            </button>

            @auth
                <button type="button" onclick="toggleWebProfileMenu(event)" title="{{ auth()->user()->name }}" class="sb-item w-full">
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-gradient-to-br from-orange-500 to-orange-600 text-xs font-extrabold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span data-sb-label class="min-w-0 flex-1 truncate text-left">{{ auth()->user()->name }}</span>
                    <i data-sb-label class="fa-solid fa-chevron-up text-[10px]"></i>
                </button>
                <div id="webProfileDropdown" class="glass-panel absolute bottom-full left-3 right-3 z-[80] mb-2 hidden p-2 md:left-auto md:right-auto md:w-60">
                    <div class="mb-1.5 border-b border-white/10 px-3 py-2">
                        <div class="truncate text-xs font-extrabold text-white">{{ auth()->user()->name }}</div>
                        <div class="truncate text-[11px] text-slate-400">{{ auth()->user()->email }}</div>
                    </div>
                    <a href="{{ route('user.profile') }}" class="menu-item flex items-center gap-2.5 !px-3 !py-2 text-xs font-semibold">
                        <i class="fa-solid fa-user text-orange-400"></i><span>My Profile &amp; History</span>
                    </a>
                    <form method="POST" action="{{ route('user.logout') }}" class="mt-0.5">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left text-xs font-semibold text-rose-400 transition hover:bg-rose-500/15">
                            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('user.auth') }}" title="Sign In" class="btn-aura w-full !rounded-2xl !py-2.5">
                    <i class="fa-solid fa-circle-user"></i><span data-sb-label>Sign In</span>
                </a>
            @endauth
        </div>
    </aside>

    <div class="aura-shell flex min-h-screen min-w-0 flex-col">
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

    <footer class="mt-10 border-t border-white/10 py-10 text-center text-xs text-slate-500">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-extrabold text-white">
            <span class="h-2.5 w-2.5 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 shadow-[0_0_12px_var(--color-orange-500)]"></span>
            <span>Aura<span class="text-orange-400">Cinema</span></span>
        </a>
        <p class="mt-3">&copy; {{ date('Y') }} AuraCinema. Built with Laravel.</p>
        <p class="mt-2"><a href="{{ route('movies.create') }}" class="transition hover:text-orange-300">Admin</a></p>
    </footer>
    </div>{{-- /.aura-shell --}}

    <!-- Compact Floating Subtitle Notice (Bottom-Right, Non-Fullscreen) -->
    <div id="auraWelcomePopup"
         class="on-dark fixed inset-x-3 bottom-3 z-[70] hidden overflow-hidden rounded-2xl border border-orange-500/35 bg-[#12121a]/95 p-3.5 text-slate-200 shadow-2xl shadow-black/90 backdrop-blur-xl sm:inset-x-auto sm:bottom-5 sm:right-5 sm:w-[22.5rem]">
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


        // ---- Theme + sidebar (state saved in localStorage; the head script restores it before paint) ----
        (function () {
            var root = document.documentElement;
            var save = function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} };
            var meta = document.querySelector('meta[name="theme-color"]');
            var paintTheme = function () {
                var light = root.getAttribute('data-theme') === 'light';
                if (meta) meta.setAttribute('content', light ? '#f5f3ff' : '#0b0e17');
                document.querySelectorAll('[data-theme-label]').forEach(function (el) { el.textContent = light ? 'Dark mode' : 'Light mode'; });
            };
            paintTheme();
            document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
                    root.setAttribute('data-theme', next);
                    save('aura_theme', next);
                    paintTheme();
                });
            });

            var collapse = document.querySelector('[data-sb-collapse]');
            if (collapse) collapse.addEventListener('click', function () {
                var next = root.getAttribute('data-sb') === 'collapsed' ? 'expanded' : 'collapsed';
                root.setAttribute('data-sb', next);
                save('aura_sidebar', next);
            });

            var closeDrawer = function () { root.classList.remove('sb-open'); document.getElementById('sbOverlay').classList.add('hidden'); };
            var openDrawer = function () { root.classList.add('sb-open'); document.getElementById('sbOverlay').classList.remove('hidden'); };
            document.querySelectorAll('[data-sb-open]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openDrawer();
                    if (btn.hasAttribute('data-sb-focus-search')) setTimeout(function () { document.getElementById('q').focus(); }, 60);
                });
            });
            document.querySelectorAll('[data-sb-close]').forEach(function (el) { el.addEventListener('click', closeDrawer); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(); });

            // Collapsed (icons only): the search icon re-opens the full sidebar and focuses the box.
            var searchToggle = document.querySelector('[data-sb-search-toggle]');
            if (searchToggle) searchToggle.addEventListener('click', function () {
                root.setAttribute('data-sb', 'expanded');
                save('aura_sidebar', 'expanded');
                setTimeout(function () { document.getElementById('q').focus(); }, 60);
            });
        })();

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