<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — AuraCinema</title>
    <style>
        body {
            background: #0A0E17;
            color: #fff;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container { max-width: 960px; margin: 0 auto; }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }
        .back-btn {
            color: #38BDF8;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
        }
        .profile-glass {
            background: rgba(19, 27, 46, 0.82);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }
        .user-info { display: flex; align-items: center; gap: 14px; }
        .avatar {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: #2563EB;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
        }
        .badge {
            display: inline-block;
            margin-top: 6px;
            background: rgba(56, 189, 248, 0.16);
            color: #38BDF8;
            font-size: 11.5px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 8px;
        }
        .logout-btn {
            background: #EF4444;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 19px;
            font-weight: 800;
            margin-bottom: 16px;
        }
        .section-bar {
            width: 4px;
            height: 20px;
            background: #38BDF8;
            border-radius: 4px;
        }
        .history-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 14px;
        }
        .card {
            background: #131B2E;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            overflow: hidden;
            position: relative;
            transition: transform 0.18s;
        }
        .card:hover { transform: translateY(-3px); }
        .card a { text-decoration: none; color: inherit; display: block; }
        .poster-wrap { position: relative; aspect-ratio: 2 / 3; background: #0B0F1A; }
        .poster-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .progress-track {
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.22);
        }
        .progress-fill { height: 100%; background: #38BDF8; }
        .remove-btn {
            position: absolute;
            top: 6px; right: 6px;
            width: 24px; height: 24px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.72);
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 12px;
        }
        .card-body { padding: 10px; }
        .card-title {
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-meta {
            font-size: 11px;
            color: #38BDF8;
            font-weight: 700;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-bar">
            <a href="{{ url('/') }}" class="back-btn">← AuraCinema Home</a>
        </div>

        <div class="profile-glass">
            <div class="user-info">
                <div class="avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <div>
                    <div style="font-size: 20px; font-weight: 800;">{{ $user->name }}</div>
                    <div style="font-size: 13px; color: rgba(255,255,255,0.65);">{{ $user->email }}</div>
                    <span class="badge">Watched: {{ $history->count() }} titles</span>
                </div>
            </div>
            <form method="POST" action="{{ route('user.logout') }}">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>

        <div class="section-title">
            <div class="section-bar"></div>
            <span>My Watch History</span>
        </div>

        @if($history->isEmpty())
            <p style="color: rgba(255,255,255,0.6);">ඔයා තවම movies කිසිවක් බලලා නැහැ.</p>
        @else
            <div class="history-grid">
                @foreach($history as $item)
                    @php
                        $pct = $item->duration_seconds > 0
                            ? min(100, max(5, round(($item->position_seconds / $item->duration_seconds) * 100)))
                            : 0;
                    @endphp
                    <div class="card">
                        <form method="POST" action="{{ route('user.history.destroy', $item->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="remove-btn" title="Remove">✕</button>
                        </form>
                        <a href="{{ url('/movies/' . $item->movie_id) }}?t={{ $item->position_seconds }}">
                            <div class="poster-wrap">
                                @if($item->poster_url)
                                    <img src="{{ $item->poster_url }}" alt="{{ $item->title }}" loading="lazy">
                                @endif
                                @if($pct > 0)
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: {{ $pct }}%"></div>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="card-title">{{ $item->title }}</div>
                                <div class="card-meta">
                                    {{ strtoupper($item->type) }}{{ $item->episode_id > 0 ? ' • EP ' . $item->episode_id : '' }}
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>