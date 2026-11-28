<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AuraCinema — Sign In / Create Account</title>
    <style>
        :root {
            --bg-dark: #0A0E17;
            --glass-bg: rgba(19, 27, 46, 0.78);
            --glass-border: rgba(255, 255, 255, 0.10);
            --input-bg: #131B2E;
            --cyan: #38BDF8;
            --blue-btn: #2563EB;
            --blue-hover: #1D4ED8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
        body {
            background: radial-gradient(circle at 20% 15%, rgba(56, 189, 248, 0.14), transparent 40%),
                        radial-gradient(circle at 80% 85%, rgba(37, 99, 235, 0.16), transparent 45%),
                        var(--bg-dark);
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .glass-card {
            width: 100%;
            max-width: 420px;
            background: var(--glass-bg);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            padding: 28px 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.55);
        }
        .brand {
            text-align: center;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }
        .brand span { color: var(--cyan); }
        .subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.70);
            font-size: 13px;
            line-height: 1.45;
            margin-bottom: 22px;
        }
        .tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: rgba(10, 14, 23, 0.65);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 20px;
            border: 1px solid var(--glass-border);
        }
        .tab-btn {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.65);
            padding: 10px;
            border-radius: 9px;
            font-weight: 700;
            font-size: 13.5px;
            cursor: pointer;
            transition: 0.2s;
        }
        .tab-btn.active {
            background: var(--blue-btn);
            color: #fff;
        }
        .field { margin-bottom: 14px; }
        .field label {
            display: block;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.72);
            margin-bottom: 6px;
            font-weight: 600;
        }
        .field input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 12px 14px;
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        .field input:focus { border-color: var(--cyan); }
        .submit-btn {
            width: 100%;
            background: var(--blue-btn);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 13px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 6px;
            transition: background 0.2s;
        }
        .submit-btn:hover { background: var(--blue-hover); }
        .error-box {
            background: rgba(239, 68, 68, 0.16);
            border: 1px solid rgba(239, 68, 68, 0.45);
            color: #FCA5A5;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 12.5px;
            margin-bottom: 16px;
        }
        .back-link {
            display: inline-block;
            margin-top: 18px;
            color: var(--cyan);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="glass-card">
        <div class="brand">Aura<span>Cinema</span></div>
        <p class="subtitle">ඔයා බලපු movies සහ භාගෙට නතර කරපු තැන් Mobile App එකයි Website එකයි දෙකේම sync කරගන්න.</p>

        @if ($errors->any())
            <div class="error-box">
                @foreach ($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="tabs">
            <button type="button" id="tab-login" class="tab-btn {{ $initialTab !== 'register' ? 'active' : '' }}" onclick="switchAuthTab('login')">Sign In</button>
            <button type="button" id="tab-register" class="tab-btn {{ $initialTab === 'register' ? 'active' : '' }}" onclick="switchAuthTab('register')">Create Account</button>
        </div>

        <!-- Sign In Form -->
        <form id="form-login" method="POST" action="{{ route('user.login') }}" class="{{ $initialTab === 'register' ? 'hidden' : '' }}">
            @csrf
            <div class="field">
                <label>Email ලිපිනය</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="submit-btn">Sign In</button>
        </form>

        <!-- Create Account Form -->
        <form id="form-register" method="POST" action="{{ route('user.register') }}" class="{{ $initialTab !== 'register' ? 'hidden' : '' }}">
            @csrf
            <div class="field">
                <label>ඔයාගේ නම (Name)</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Nuwan" required>
            </div>
            <div class="field">
                <label>Email ලිපිනය</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required>
            </div>
            <div class="field">
                <label>Password (අකුරු 6කට වැඩි)</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="field">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" placeholder="••••••••" required>
            </div>
            <button type="submit" class="submit-btn">Create Account</button>
        </form>
    </div>

    <a href="{{ url('/') }}" class="back-link">← Back to AuraCinema Home</a>

    <script>
        function switchAuthTab(mode) {
            document.getElementById('tab-login').classList.toggle('active', mode === 'login');
            document.getElementById('tab-register').classList.toggle('active', mode === 'register');
            document.getElementById('form-login').classList.toggle('hidden', mode !== 'login');
            document.getElementById('form-register').classList.toggle('hidden', mode !== 'register');
        }
    </script>
</body>
</html>