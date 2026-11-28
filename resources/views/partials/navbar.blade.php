<style>
    .aura-navbar {
        position: sticky;
        top: 0;
        z-index: 1000;
        width: 100%;
        background: rgba(10, 14, 23, 0.82);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.09);
        font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    }
    .aura-nav-container {
        max-width: 1280px;
        margin: 0 auto;
        padding: 12px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }
    .aura-brand {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        text-decoration: none;
        flex-shrink: 0;
    }
    .aura-brand-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: linear-gradient(135deg, #38BDF8, #2563EB);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 900;
        font-size: 17px;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.45);
    }
    .aura-brand-text {
        font-size: 20px;
        font-weight: 800;
        color: #fff;
        letter-spacing: -0.4px;
    }
    .aura-brand-text span {
        color: #38BDF8;
    }
    .aura-nav-links {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .aura-nav-link {
        color: rgba(255, 255, 255, 0.75);
        text-decoration: none;
        font-size: 13.5px;
        font-weight: 600;
        padding: 7px 13px;
        border-radius: 999px;
        transition: 0.2s;
    }
    .aura-nav-link:hover,
    .aura-nav-link.active {
        color: #fff;
        background: rgba(56, 189, 248, 0.16);
    }
    .aura-nav-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .aura-search-box {
        display: flex;
        align-items: center;
        background: rgba(19, 27, 46, 0.85);
        border: 1px solid rgba(255, 255, 255, 0.10);
        border-radius: 999px;
        padding: 5px 12px;
    }
    .aura-search-box input {
        background: transparent;
        border: none;
        outline: none;
        color: #fff;
        font-size: 13px;
        width: 165px;
    }
    .aura-search-box input::placeholder {
        color: rgba(255, 255, 255, 0.45);
    }
    .aura-search-box button {
        background: transparent;
        border: none;
        color: #38BDF8;
        cursor: pointer;
        display: flex;
        align-items: center;
        padding: 2px;
    }

    /* Guest Sign In / Register Buttons */
    .aura-btn-signin {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(19, 27, 46, 0.9);
        border: 1px solid rgba(56, 189, 248, 0.4);
        color: #38BDF8;
        padding: 7px 14px;
        border-radius: 999px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        transition: 0.2s;
        white-space: nowrap;
    }
    .aura-btn-signin:hover {
        background: rgba(56, 189, 248, 0.16);
        color: #fff;
    }
    .aura-btn-register {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #2563EB;
        border: 1px solid #2563EB;
        color: #fff;
        padding: 7px 15px;
        border-radius: 999px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        transition: 0.2s;
        white-space: nowrap;
    }
    .aura-btn-register:hover {
        background: #1D4ED8;
    }

    /* Logged-in Profile Dropdown */
    .aura-profile-wrap {
        position: relative;
    }
    .aura-profile-trigger {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(37, 99, 235, 0.20);
        border: 1px solid rgba(56, 189, 248, 0.45);
        color: #fff;
        padding: 5px 12px 5px 6px;
        border-radius: 999px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 700;
        transition: 0.2s;
    }
    .aura-profile-trigger:hover {
        background: rgba(37, 99, 235, 0.34);
    }
    .aura-avatar-circle {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563EB, #38BDF8);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 800;
    }
    .aura-dropdown-menu {
        position: absolute;
        right: 0;
        top: calc(100% + 10px);
        width: 225px;
        background: rgba(19, 27, 46, 0.96);
        backdrop-filter: blur(22px);
        -webkit-backdrop-filter: blur(22px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        padding: 8px;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.65);
        display: none;
        z-index: 1100;
    }
    .aura-dropdown-menu.open {
        display: block;
    }
    .aura-dropdown-header {
        padding: 8px 10px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        margin-bottom: 6px;
    }
    .aura-dropdown-name {
        color: #fff;
        font-size: 13.5px;
        font-weight: 700;
    }
    .aura-dropdown-email {
        color: rgba(255, 255, 255, 0.55);
        font-size: 11.5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .aura-dropdown-item {
        display: flex;
        align-items: center;
        gap: 9px;
        width: 100%;
        padding: 9px 10px;
        border-radius: 10px;
        color: rgba(255, 255, 255, 0.88);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        background: transparent;
        border: none;
        cursor: pointer;
        text-align: left;
        transition: 0.15s;
    }
    .aura-dropdown-item:hover {
        background: rgba(56, 189, 248, 0.15);
        color: #fff;
    }
    .aura-dropdown-logout {
        color: #F87171;
    }
    .aura-dropdown-logout:hover {
        background: rgba(239, 68, 68, 0.16);
        color: #FCA5A5;
    }

    /* Mobile Hamburger */
    .aura-mobile-toggle {
        display: none;
        background: rgba(19, 27, 46, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #fff;
        border-radius: 10px;
        padding: 6px 9px;
        cursor: pointer;
    }
    .aura-mobile-drawer {
        display: none;
        padding: 12px 18px 18px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(10, 14, 23, 0.96);
    }
    .aura-mobile-drawer.open {
        display: block;
    }
    .aura-mobile-links {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-top: 12px;
    }

    @media (max-width: 860px) {
        .aura-nav-links,
        .aura-search-box,
        .aura-desktop-auth {
            display: none !important;
        }
        .aura-mobile-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    }
</style>

<header class="aura-navbar">
    <div class="aura-nav-container">
        <!-- Brand Logo -->
        <a href="{{ url('/') }}" class="aura-brand">
            <div class="aura-brand-icon">A</div>
            <div class="aura-brand-text">Aura<span>Cinema</span></div>
        </a>

        <!-- Desktop Navigation Links -->
        <nav class="aura-nav-links">
            <a href="{{ url('/') }}" class="aura-nav-link {{ request()->is('/') && !request('type') ? 'active' : '' }}">Home</a>
            <a href="{{ url('/?type=movie') }}" class="aura-nav-link {{ request('type') === 'movie' ? 'active' : '' }}">Movies</a>
            <a href="{{ url('/?type=series') }}" class="aura-nav-link {{ request('type') === 'series' ? 'active' : '' }}">Series</a>
            <a href="{{ url('/?type=anime') }}" class="aura-nav-link {{ request('type') === 'anime' ? 'active' : '' }}">Anime</a>
        </nav>

        <!-- Right Side: Search + Account / Profile -->
        <div class="aura-nav-right">
            <form action="{{ url('/') }}" method="GET" class="aura-search-box">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search movies, series...">
                <button type="submit" title="Search">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </form>

            @auth
                <!-- Logged-in User Profile Button & Dropdown -->
                <div class="aura-profile-wrap">
                    <button type="button" class="aura-profile-trigger" onclick="toggleAuraProfileMenu(event)">
                        <span class="aura-avatar-circle">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span>{{ auth()->user()->name }}</span>
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>

                    <div id="auraProfileDropdown" class="aura-dropdown-menu">
                        <div class="aura-dropdown-header">
                            <div class="aura-dropdown-name">{{ auth()->user()->name }}</div>
                            <div class="aura-dropdown-email">{{ auth()->user()->email }}</div>
                        </div>
                        <a href="{{ route('user.profile') }}" class="aura-dropdown-item">
                            <svg width="16" height="16" fill="none" stroke="#38BDF8" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            My Profile & History
                        </a>
                        <form method="POST" action="{{ route('user.logout') }}" style="margin: 0;">
                            @csrf
                            <button type="submit" class="aura-dropdown-item aura-dropdown-logout">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <!-- Guest Sign In / Create Account Buttons -->
                <div class="aura-desktop-auth" style="display: flex; align-items: center; gap: 8px;">
                    <a href="{{ route('user.auth') }}" class="aura-btn-signin">Sign In</a>
                    <a href="{{ route('user.auth', ['mode' => 'register']) }}" class="aura-btn-register">Create Account</a>
                </div>
            @endauth

            <!-- Mobile Menu Button -->
            <button type="button" class="aura-mobile-toggle" onclick="toggleAuraMobileMenu()" aria-label="Menu">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Responsive Drawer -->
    <div id="auraMobileDrawer" class="aura-mobile-drawer">
        <form action="{{ url('/') }}" method="GET" style="display: flex; gap: 8px;">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search movies, series..."
                style="flex: 1; background: #131B2E; border: 1px solid rgba(255,255,255,0.12); border-radius: 10px; padding: 9px 12px; color: #fff; font-size: 13.5px; outline: none;">
            <button type="submit" style="background: #2563EB; color: #fff; border: none; border-radius: 10px; padding: 0 14px; font-weight: 700;">Search</button>
        </form>

        <div class="aura-mobile-links">
            <a href="{{ url('/') }}" class="aura-nav-link">Home</a>
            <a href="{{ url('/?type=movie') }}" class="aura-nav-link">Movies</a>
            <a href="{{ url('/?type=series') }}" class="aura-nav-link">Series</a>
            <a href="{{ url('/?type=anime') }}" class="aura-nav-link">Anime</a>

            @auth
                <a href="{{ route('user.profile') }}" class="aura-nav-link" style="color: #38BDF8;">
                    My Profile ({{ auth()->user()->name }})
                </a>
                <form method="POST" action="{{ route('user.logout') }}" style="margin-top: 4px;">
                    @csrf
                    <button type="submit" style="width: 100%; background: rgba(239,68,68,0.18); border: 1px solid rgba(239,68,68,0.4); color: #FCA5A5; padding: 9px; border-radius: 10px; font-weight: 700; cursor: pointer;">
                        Logout
                    </button>
                </form>
            @else
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px;">
                    <a href="{{ route('user.auth') }}" class="aura-btn-signin" style="justify-content: center;">Sign In</a>
                    <a href="{{ route('user.auth', ['mode' => 'register']) }}" class="aura-btn-register" style="justify-content: center;">Create Account</a>
                </div>
            @endauth
        </div>
    </div>
</header>

<script>
    function toggleAuraProfileMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('auraProfileDropdown');
        if (menu) menu.classList.toggle('open');
    }

    function toggleAuraMobileMenu() {
        const drawer = document.getElementById('auraMobileDrawer');
        if (drawer) drawer.classList.toggle('open');
    }

    document.addEventListener('click', function () {
        const menu = document.getElementById('auraProfileDropdown');
        if (menu) menu.classList.remove('open');
    });
</script>