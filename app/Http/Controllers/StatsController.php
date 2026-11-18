<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\PageView;
use Illuminate\Support\Carbon;

class StatsController extends Controller
{
    public function index()
    {
        $tz = config('auracinema.stats_timezone');
        $today = now($tz)->startOfDay();
        $since30 = $today->copy()->subDays(29)->toDateString();

        // One row per day: page views and unique visitors for that day.
        $daily = PageView::selectRaw('viewed_on, count(*) as views, count(distinct visitor) as visitors')
            ->where('viewed_on', '>=', $since30)
            ->groupBy('viewed_on')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->viewed_on)->toDateString());

        $days = collect(range(29, 0))->map(function (int $ago) use ($today, $daily) {
            $date = $today->copy()->subDays($ago);
            $row = $daily->get($date->toDateString());

            return ['date' => $date, 'views' => (int) ($row->views ?? 0), 'visitors' => (int) ($row->visitors ?? 0)];
        });

        $sum = fn ($rows) => ['views' => $rows->sum('views'), 'visitors' => $rows->sum('visitors')];

        $topIds = PageView::selectRaw('movie_id, count(*) as views, count(distinct visitor) as visitors')
            ->whereNotNull('movie_id')->where('viewed_on', '>=', $since30)
            ->groupBy('movie_id')->orderByDesc('views')->limit(10)->get();
        $titles = Movie::whereIn('id', $topIds->pluck('movie_id'))->pluck('title', 'id');

        return view('admin.stats', [
            'today' => $sum($days->slice(-1)),
            'week' => $sum($days->slice(-7)),
            'month' => $sum($days),
            'allViews' => PageView::count(),
            'chart' => $days->slice(-14)->values(),
            'chartMax' => max(1, (int) $days->slice(-14)->max('views')),
            'topMovies' => $topIds->filter(fn ($r) => $titles->has($r->movie_id))->values(),
            'titles' => $titles,
            'referrers' => PageView::selectRaw('referrer_host, count(*) as views')
                ->whereNotNull('referrer_host')->where('viewed_on', '>=', $since30)
                ->groupBy('referrer_host')->orderByDesc('views')->limit(8)->get(),
            'direct' => PageView::whereNull('referrer_host')->where('viewed_on', '>=', $since30)->count(),
            'devices' => PageView::selectRaw('device, count(*) as views')
                ->where('viewed_on', '>=', $since30)->groupBy('device')->pluck('views', 'device'),
        ]);
    }
}
