@extends('layouts.app')

@section('title', 'Visitor stats')
@section('robots', 'noindex,nofollow')

@section('content')
    <h1 class="mb-1 text-lg font-bold">Visitor stats</h1>
    <p class="mb-5 text-xs text-slate-500">
        Visitors = different people per day (counted anonymously, no IP address is stored). Bots and your own admin browser are not counted.
        Counting started when this feature was deployed.
    </p>

    {{-- Headline numbers --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach([['Today', $today], ['Last 7 days', $week], ['Last 30 days', $month]] as [$label, $data])
            <div class="rounded-xl border border-white/10 bg-zinc-900/60 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                <p class="mt-1 text-3xl font-extrabold text-white">{{ number_format($data['visitors']) }}</p>
                <p class="text-xs text-slate-500">visitors &middot; {{ number_format($data['views']) }} page views</p>
            </div>
        @endforeach
        <div class="rounded-xl border border-white/10 bg-zinc-900/60 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">All time</p>
            <p class="mt-1 text-3xl font-extrabold text-white">{{ number_format($allViews) }}</p>
            <p class="text-xs text-slate-500">page views</p>
        </div>
    </div>

    {{-- Last 14 days --}}
    <section class="mb-6 rounded-xl border border-white/10 bg-zinc-900/60 p-4">
        <h2 class="mb-3 text-sm font-bold text-white">Last 14 days <span class="font-normal text-slate-500">(visitors, with page views on hover)</span></h2>
        <div class="flex h-40 items-end gap-1.5">
            @foreach($chart as $day)
                <div class="group flex h-full flex-1 flex-col items-center justify-end" title="{{ $day['date']->format('d M') }}: {{ $day['visitors'] }} visitors, {{ $day['views'] }} views">
                    <span class="mb-1 text-[10px] font-semibold text-slate-300">{{ $day['visitors'] ?: '' }}</span>
                    <div class="w-full rounded-t bg-gradient-to-t from-orange-600 to-amber-400" style="height: {{ max(2, round($day['views'] / $chartMax * 100)) }}%"></div>
                </div>
            @endforeach
        </div>
        <div class="mt-1 flex gap-1.5">
            @foreach($chart as $day)
                <span class="flex-1 text-center text-[10px] text-slate-500">{{ $day['date']->format('d') }}</span>
            @endforeach
        </div>
        <p class="mt-1 text-[11px] text-slate-500">Bar height = page views. Number on top = visitors.</p>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Most viewed --}}
        <section class="rounded-xl border border-white/10 bg-zinc-900/60 p-4">
            <h2 class="mb-3 text-sm font-bold text-white">Most viewed titles <span class="font-normal text-slate-500">(30 days)</span></h2>
            @forelse($topMovies as $row)
                <a href="{{ route('movies.show', $row->movie_id) }}" class="flex items-center justify-between gap-3 border-b border-white/5 py-2 text-sm last:border-0 hover:text-orange-300">
                    <span class="truncate">{{ $loop->iteration }}. {{ $titles[$row->movie_id] }}</span>
                    <span class="shrink-0 text-xs text-slate-400">{{ number_format($row->visitors) }} visitors &middot; {{ number_format($row->views) }} views</span>
                </a>
            @empty
                <p class="text-sm text-slate-500">No movie views yet.</p>
            @endforelse
        </section>

        {{-- Where visitors come from + devices --}}
        <section class="rounded-xl border border-white/10 bg-zinc-900/60 p-4">
            <h2 class="mb-3 text-sm font-bold text-white">Where visitors come from <span class="font-normal text-slate-500">(30 days, page views)</span></h2>
            <div class="flex items-center justify-between border-b border-white/5 py-2 text-sm">
                <span>Direct / WhatsApp / apps</span><span class="text-xs text-slate-400">{{ number_format($direct) }}</span>
            </div>
            @foreach($referrers as $ref)
                <div class="flex items-center justify-between border-b border-white/5 py-2 text-sm last:border-0">
                    <span class="truncate">{{ $ref->referrer_host }}</span><span class="text-xs text-slate-400">{{ number_format($ref->views) }}</span>
                </div>
            @endforeach

            <h2 class="mb-2 mt-5 text-sm font-bold text-white">Devices <span class="font-normal text-slate-500">(30 days)</span></h2>
            <p class="text-sm text-slate-300">
                <i class="fa-solid fa-mobile-screen text-orange-400"></i> Mobile {{ number_format($devices['mobile'] ?? 0) }}
                &nbsp;&middot;&nbsp;
                <i class="fa-solid fa-desktop text-orange-400"></i> Desktop {{ number_format($devices['desktop'] ?? 0) }}
            </p>
        </section>
    </div>
@endsection
