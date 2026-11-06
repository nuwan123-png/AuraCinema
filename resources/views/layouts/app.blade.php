<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AuraCinema - Cloud Stream & Discovery</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-card {
            background: rgba(18, 28, 54, 0.55);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-card:hover {
            border-color: rgba(56, 189, 248, 0.35);
            box-shadow: 0 10px 30px -10px rgba(14, 165, 233, 0.25);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-[#060a14] via-[#0d162f] to-[#040711] text-slate-100 min-h-screen flex flex-col font-sans selection:bg-orange-500 selection:text-white relative overflow-x-hidden">

    <!-- Ambient Glowing Light Bubbles -->
    <div class="fixed top-[-10%] left-[-10%] w-[500px] h-[500px] bg-blue-600/15 rounded-full blur-[130px] pointer-events-none -z-10"></div>
    <div class="fixed bottom-[-10%] right-[-10%] w-[500px] h-[500px] bg-orange-600/10 rounded-full blur-[140px] pointer-events-none -z-10"></div>

    <!-- Glass Navbar -->
    <header class="sticky top-0 z-50 px-6 py-4">
        <div class="max-w-7xl mx-auto glass-panel rounded-2xl px-6 py-3 flex items-center justify-between shadow-2xl">
            <!-- Brand Logo & Nav Tabs -->
            <div class="flex items-center space-x-6">
                <a href="{{ route('movies.index') }}" class="flex items-center gap-2 group">
                    <div class="w-3 h-3 rounded-full bg-orange-500 group-hover:scale-125 transition-all shadow-[0_0_12px_#f97316]"></div>
                    <span class="text-xl font-bold tracking-tight text-white">Aura<span class="text-orange-400">Cinema</span></span>
                </a>

                <div class="hidden md:flex items-center space-x-2">
                    <a href="{{ route('movies.index') }}" class="px-4 py-1.5 rounded-full text-xs font-medium bg-white/10 text-white border border-white/15 hover:bg-white/20 transition">
                        All Movies
                    </a>
                    <a href="#" class="px-4 py-1.5 rounded-full text-xs font-medium text-slate-400 hover:text-white hover:bg-white/5 transition">
                        Genres
                    </a>
                    <a href="#" class="px-4 py-1.5 rounded-full text-xs font-medium text-slate-400 hover:text-white hover:bg-white/5 transition">
                        Trending
                    </a>
                </div>
            </div>

            <!-- Search & Upload Action Button -->
            <div class="flex items-center space-x-4">
                <div class="relative hidden sm:block">
                    <input type="text" placeholder="Search movies, trailers..." class="bg-black/30 text-xs text-white pl-9 pr-4 py-2 rounded-xl border border-white/10 w-60 focus:outline-none focus:border-cyan-400/50 transition placeholder-slate-500">
                    <i class="fa fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                </div>

                <a href="{{ route('movies.create') }}" class="flex items-center gap-2 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-lg shadow-orange-500/25 transition transform hover:-translate-y-0.5">
                    <i class="fa fa-plus text-[10px]"></i>
                    <span>+ Add Movie</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-6 py-6">
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl glass-panel border border-emerald-500/30 text-emerald-300 text-xs flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa fa-check-circle text-emerald-400"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Full-Width Glass Footer (කලින් විදියටම දිගට columns සහිතව) -->
    <footer class="glass-panel mt-20 border-t border-white/10 text-slate-400 text-xs">
        <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h4 class="text-white font-bold mb-3 uppercase tracking-wider text-xs">Browse Platform</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="hover:text-cyan-400 transition">Featured Releases</a></li>
                    <li><a href="#" class="hover:text-cyan-400 transition">Action & Thriller</a></li>
                    <li><a href="#" class="hover:text-cyan-400 transition">Sci-Fi & Fantasy</a></li>
                    <li><a href="#" class="hover:text-cyan-400 transition">Documentaries</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold mb-3 uppercase tracking-wider text-xs">Community & Links</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="hover:text-cyan-400 transition">Top Rated Vault</a></li>
                    <li><a href="#" class="hover:text-cyan-400 transition">Trailers Showcase</a></li>
                    <li><a href="#" class="hover:text-cyan-400 transition">Upcoming 2026/2027</a></li>
                    <li><a href="#" class="hover:text-cyan-400 transition">User Collections</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold mb-3 uppercase tracking-wider text-xs">Stay Connected</h4>
                <p class="mb-3 text-[11px] text-slate-400 leading-relaxed">Follow our developer logs and new movie catalog updates directly from the studio.</p>
                <div class="flex space-x-3 text-base text-slate-300">
                    <a href="#" class="w-8 h-8 rounded-lg glass-panel flex items-center justify-center hover:text-cyan-400 hover:border-cyan-400/40 transition"><i class="fab fa-github"></i></a>
                    <a href="#" class="w-8 h-8 rounded-lg glass-panel flex items-center justify-center hover:text-cyan-400 hover:border-cyan-400/40 transition"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="w-8 h-8 rounded-lg glass-panel flex items-center justify-center hover:text-cyan-400 hover:border-cyan-400/40 transition"><i class="fab fa-discord"></i></a>
                </div>
            </div>

            <div class="flex flex-col items-start md:items-end text-left md:text-right">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-orange-500 shadow-[0_0_10px_#f97316]"></div>
                    <span class="text-xl font-black text-white tracking-tight">Aura<span class="text-orange-400">Cinema</span></span>
                </div>
                <p class="text-[11px] text-slate-400 max-w-xs leading-relaxed">Modern Glassmorphic Private Media Vault & Trailer Showcase.</p>
                <p class="text-[10px] text-slate-500 mt-2 max-w-xs">Hosted securely on AWS EC2 Infrastructure with automated CI/CD deployment.</p>
            </div>
        </div>

        <div class="border-t border-white/5 py-4 px-6 text-center text-[11px] text-slate-500">
            © {{ date('Y') }} AuraCinema Engine. Built with Laravel, Tailwind CSS & Glass UI.
        </div>
    </footer>
    
<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
</body>
</html>