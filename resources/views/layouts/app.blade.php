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

    <header class="sticky top-0 z-50 border-b border-white/5 bg-[#0b0b0f]/95">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="AuraCinema home">
                <span class="h-3 w-3 rounded-full bg-orange-500"></span>
                <span class="text-xl font-extrabold tracking-tight">Aura<span class="text-orange-400">Cinema</span></span>
            </a>

            <nav class="hidden items-center gap-5 text-sm text-slate-300 md:flex" aria-label="Primary">
                <a href="{{ route('home') }}" class="hover:text-white">Home</a>
                <a href="{{ route('home') }}#new" class="hover:text-white">New</a>
                <a href="{{ route('home') }}#all" class="hover:text-white">All titles</a>
            </nav>

            <form action="{{ route('movies.index') }}" method="GET" role="search" class="relative w-full max-w-xs">
                <label for="q" class="sr-only">Search movies</label>
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-500"></i>
                <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Search titles" autocomplete="off"
                       class="w-full rounded-md border border-white/10 bg-white/5 py-2 pl-8 pr-3 text-sm text-white placeholder-slate-500 focus:border-orange-400/60 focus:outline-none">
            </form>

            @if(request()->cookie('aura_admin'))
                <a href="{{ route('movies.create') }}" class="hidden shrink-0 rounded-md bg-orange-500 px-3 py-2 text-sm font-bold text-white hover:bg-orange-600 sm:inline-flex">+ Add movie</a>
            @endif
        </div>
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
