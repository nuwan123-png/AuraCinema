<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'AuraCinema') }}</title>
        
        <!-- Font Awesome for icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <!-- Add Tailwind CSS from CDN for fallback if Vite is not running -->
            <script src="https://cdn.tailwindcss.com"></script>
        @endif
    </head>
    <body class="bg-[#0a0a0a] text-[#EDEDEC] min-h-screen">
        
        <!-- Header / Navigation -->
        <header class="w-full bg-[#161615] shadow-md px-6 py-4 flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-orange-500">AuraCinema</h1>
            @if (Route::has('login'))
                <nav class="flex items-center gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm hover:text-orange-400">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm hover:text-orange-400">Log in</a>
                    @endauth
                </nav>
            @endif
        </header>

        <main class="container mx-auto px-4 lg:px-8 pb-12">
            
            <!-- (NEW) Watch the Trailers Section -->
            @if(isset($upcomingMovies) && $upcomingMovies->isNotEmpty())
            <div class="mb-12">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-film text-orange-500"></i> Watch the Trailers <span class="text-sm font-normal text-gray-400">(ළඟදීම)</span>
                    </h2>
                </div>
                
                <div class="flex overflow-x-auto pb-4 gap-4 snap-x hide-scrollbar">
                    @foreach($upcomingMovies as $movie)
                        <!-- Passing isUpcoming = true -->
                        @include('movies._card', ['movie' => $movie, 'isUpcoming' => true])
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Regular Latest Movies Section -->
            @if(isset($movies) && $movies->isNotEmpty())
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-white mb-4">Latest Additions</h2>
                <div class="flex flex-wrap gap-4 justify-center sm:justify-start">
                    @foreach($movies as $movie)
                        @include('movies._card', ['movie' => $movie])
                    @endforeach
                </div>
            </div>
            @endif

        </main>
        
        <style>
            /* Helper to hide scrollbar but keep functionality */
            .hide-scrollbar::-webkit-scrollbar { display: none; }
            .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        </style>
    </body>
</html>