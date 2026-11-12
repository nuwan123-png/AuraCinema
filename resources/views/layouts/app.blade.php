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
</head>
<body class="flex min-h-screen flex-col font-sans text-slate-100 antialiased selection:bg-orange-500 selection:text-white">

    {{-- Nav helpers --}}
    @php($navType = request('type'))
    @php($navLink = fn (bool $active) => 'py-1.5 transition '.($active ? 'text-orange-400' : 'hover:text-orange-300'))

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
                    <div class="absolute inset-x-4 top-full z-50 mt-2 grid max-h-72 grid-cols-3 gap-1 sm:inset-x-auto sm:left-0 sm:w-60 sm:grid-cols-2 overflow-y-auto glass-panel p-2 text-xs normal-case">
                        @foreach($globalNavGenres as $g)
                            <a href="{{ route('movies.index', ['genre' => $g]) }}" class="menu-item {{ request('genre') === $g ? 'text-orange-400' : '' }}">{{ $g }}</a>
                        @endforeach
                    </div>
                </details>

                {{-- Type and Language Menus --}}
                @foreach($globalNavTypes as $navKey => $navLabel)
                    <div class="group relative">
                        <a href="{{ route('movies.index', ['type' => $navKey]) }}" class="flex items-center gap-1 {{ $navLink($navType === $navKey) }}">
                            {{ $navLabel }} <i class="fa-solid fa-chevron-down text-[9px]"></i>
                        </a>
                        <div class="invisible absolute left-0 top-full z-50 pt-2 opacity-0 transition duration-150 group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100">
                            <div class="glass-panel grid w-52 grid-cols-2 gap-1 p-2 text-xs normal-case">
                                <a href="{{ route('movies.index', ['type' => $navKey]) }}" class="menu-item col-span-2 font-bold text-orange-400">All {{ $navLabel }}</a>
                                @foreach($globalNavLanguages as $lang)
                                    <a href="{{ route('movies.index', ['type' => $navKey, 'language' => $lang]) }}" class="menu-item {{ $navType === $navKey && request('language') === $lang ? 'text-orange-400' : '' }}">{{ $lang }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                <a href="{{ route('suggestions') }}" class="{{ $navLink(request()->routeIs('suggestions')) }}">Suggestion</a>

                {{-- Year Dropdown --}}
                <details data-menu class="sm:relative">
                    <summary class="flex cursor-pointer list-none items-center gap-1 py-1.5 hover:text-white [&::-webkit-details-marker]:hidden {{ request('year') ? 'text-orange-400' : '' }}">
                        Year <i class="fa-solid fa-chevron-down text-[9px]"></i>
                    </summary>
                    <div class="absolute inset-x-4 top-full z-50 mt-2 glass-panel grid max-h-72 grid-cols-4 gap-1 overflow-y-auto p-2 text-xs normal-case sm:inset-x-auto sm:left-0 sm:w-52 sm:grid-cols-3">
                        @php($navYears = range((int) date('Y') + 1, 2000))
                        @foreach($navYears as $y)
                            <a href="{{ route('movies.index', ['year' => $y]) }}" class="menu-item text-center {{ (int) request('year') === $y ? 'text-orange-400' : '' }}">{{ $y }}</a>
                        @endforeach
                    </div>
                </details>

                <a href="{{ route('request.create') }}" class="{{ $navLink(request()->routeIs('request.create')) }}">Request</a>
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
</body>
</html>