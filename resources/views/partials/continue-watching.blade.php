@auth
@php
    $auraContinueItems = \App\Models\WatchHistory::where('user_id', auth()->id())
        ->orderByDesc('updated_at')
        ->limit(20)
        ->get();
@endphp

@if($auraContinueItems->isNotEmpty())
<section style="max-width: 1280px; margin: 18px auto 8px; padding: 0 16px;">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
        <span style="width: 4px; height: 20px; background: #38BDF8; border-radius: 4px; display: inline-block;"></span>
        <h2 style="color: #fff; font-size: 18px; font-weight: 800; margin: 0;">Continue Watching</h2>
    </div>

    <div style="display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px; scrollbar-width: thin;">
        @foreach($auraContinueItems as $item)
            @php
                $pct = $item->duration_seconds > 0
                    ? min(100, max(5, round(($item->position_seconds / $item->duration_seconds) * 100)))
                    : 0;
            @endphp
            <div style="flex: 0 0 132px; background: rgba(19, 27, 46, 0.78); backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,0.09); border-radius: 13px; overflow: hidden; position: relative;">
                <form method="POST" action="{{ route('user.history.destroy', $item->id) }}" style="position: absolute; top: 5px; right: 5px; z-index: 2;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="width: 22px; height: 22px; border-radius: 50%; border: none; background: rgba(0,0,0,0.72); color: #fff; font-size: 11px; cursor: pointer;">✕</button>
                </form>

                <a href="{{ url('/movies/' . $item->movie_id) }}?t={{ $item->position_seconds }}" style="text-decoration: none; color: inherit; display: block;">
                    <div style="position: relative; height: 178px; background: #0B0F1A;">
                        @if($item->poster_url)
                            <img src="{{ $item->poster_url }}" alt="{{ $item->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                        @endif
                        @if($pct > 0)
                            <div style="position: absolute; left: 0; right: 0; bottom: 0; height: 4px; background: rgba(255,255,255,0.22);">
                                <div style="height: 100%; width: {{ $pct }}%; background: #38BDF8;"></div>
                            </div>
                        @endif
                    </div>
                    <div style="padding: 8px;">
                        <div style="color: #fff; font-size: 12px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item->title }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</section>
@endif
@endauth