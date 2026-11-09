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
<body class="flex min-h-screen flex-col bg-[#0b0b0f] font-sans text-slate-100 antialiased selection:bg-orange-500 selection:text-white">

    {{-- Nav helpers --}}
    @php($navType = request('type'))
    @php($navLink = fn (bool $active) => 'py-1.5 '.($active ? 'text-orange-400' : 'hover:text-white'))

    <header class="z-50 border-b border-white/5 bg-[#0b0b0f]/95 md:sticky md:top-0">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2" aria-label="AuraCinema home">
                <span class="h-3 w-3 rounded-full bg-orange-500"></span>
                <span class="text-xl font-extrabold tracking-tight">Aura<span class="text-orange-400">Cinema</span></span>
            </a>

            <form action="{{ route('movies.index') }}" method="GET" role="search" class="relative w-full max-w-xs">
                <label for="q" class="sr-only">Search movies</label>
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-500"></i>
                <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Search titles" autocomplete="off"
                       class="w-full rounded-md border border-white/10 bg-white/5 py-2 pl-8 pr-3 text-sm text-white placeholder-slate-500 focus:border-orange-400/60 focus:outline-none">
            </form>

            @if(request()->cookie('aura_admin'))
                <div class="hidden shrink-0 gap-2 sm:flex">
                    <a href="{{ route('requests.index') }}" class="inline-flex rounded-md bg-white/10 px-3 py-2 text-sm font-semibold text-white hover:bg-white/20">Requests</a>
                    <a href="{{ route('movies.create') }}" class="inline-flex rounded-md bg-orange-500 px-3 py-2 text-sm font-bold text-white hover:bg-orange-600">+ Add movie</a>
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
                    <div class="absolute inset-x-4 top-full z-50 mt-2 grid max-h-72 grid-cols-3 gap-1 sm:inset-x-auto sm:left-0 sm:w-60 sm:grid-cols-2 overflow-y-auto rounded-md border border-white/10 bg-zinc-900 p-2 text-xs normal-case shadow-xl">
                        @foreach(config('auracinema.genres', ['Action', 'Adventure', 'Animation', 'Comedy', 'Crime', 'Documentary', 'Drama', 'Horror', 'Mystery', 'Romance', 'Sci-Fi', 'Thriller']) as $g)
                            <a href="{{ route('movies.index', ['genre' => $g]) }}" class="rounded px-2 py-1.5 hover:bg-white/10 {{ request('genre') === $g ? 'text-orange-400' : '' }}">{{ $g }}</a>
                        @endforeach
                    </div>
                </details>

                {{-- Type and Language Menus --}}
                @foreach(['movie' => 'Movies', 'series' => 'Drama / Series', 'anime' => 'Anime'] as $navKey => $navLabel)
                    <div class="group relative">
                        <a href="{{ route('movies.index', ['type' => $navKey]) }}" class="flex items-center gap-1 {{ $navLink($navType === $navKey) }}">
                            {{ $navLabel }} <i class="fa-solid fa-chevron-down text-[9px]"></i>
                        </a>
                        <div class="invisible absolute left-0 top-full z-50 pt-2 opacity-0 transition duration-150 group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100">
                            <div class="grid w-48 grid-cols-2 gap-1 rounded-md border border-white/10 bg-zinc-900 p-2 text-xs normal-case shadow-xl">
                                <a href="{{ route('movies.index', ['type' => $navKey]) }}" class="col-span-2 rounded px-2 py-1.5 font-bold text-orange-400 hover:bg-white/10">All {{ $navLabel }}</a>
                                @foreach(config('auracinema.languages', ['Sinhala', 'English', 'Tamil', 'Hindi', 'Malayalam', 'Telugu', 'Korean']) as $lang)
                                    <a href="{{ route('movies.index', ['type' => $navKey, 'language' => $lang]) }}" class="rounded px-2 py-1.5 hover:bg-white/10 {{ $navType === $navKey && request('language') === $lang ? 'text-orange-400' : '' }}">{{ $lang }}</a>
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
                    <div class="absolute inset-x-4 top-full z-50 mt-2 grid max-h-72 grid-cols-4 gap-1 overflow-y-auto rounded-md border border-white/10 bg-zinc-900 p-2 text-xs normal-case shadow-xl sm:inset-x-auto sm:left-0 sm:w-52 sm:grid-cols-3">
                        @foreach(range((int) date('Y') + 1, 2000) as $y)
                            <a href="{{ route('movies.index', ['year' => $y]) }}" class="rounded px-2 py-1.5 text-center hover:bg-white/10 {{ (int) request('year') === $y ? 'text-orange-400' : '' }}">{{ $y }}</a>
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

    <footer class="border-t border-white/5 py-8 text-center text-xs text-slate-500">
        <p>&copy; {{ date('Y') }} AuraCinema. Built with Laravel.</p>
        <p class="mt-2"><a href="{{ route('movies.create') }}" class="hover:text-slate-300">Admin</a></p>
    </footer>
</body>
</html>