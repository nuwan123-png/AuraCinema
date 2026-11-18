<?php

namespace App\Http\Middleware;

use App\Models\Movie;
use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Privacy-friendly visitor counter for the public pages.
 *
 * Runs AFTER the response is sent (terminate), so it never slows a page down, and it swallows
 * every error so a statistics problem can never break the site. No IP address is stored: a visitor
 * is a keyed hash of IP + browser + day, so the same person counts once per day and cannot be
 * followed from one day to the next. Bots, admin browsers and non-page requests are skipped.
 */
class TrackVisit
{
    private const BOTS = '/bot|crawl|spider|slurp|facebookexternalhit|facebot|whatsapp|telegram|preview|monitor|uptime|health|curl|wget|python|java\/|go-http|httpclient|headless|lighthouse|pingdom|ahrefs|semrush|bingpreview/i';

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            $agent = (string) $request->userAgent();

            if (! $request->isMethod('GET')
                || $response->getStatusCode() !== 200
                || $request->ajax()
                || $request->cookie('aura_admin')
                || $agent === ''
                || preg_match(self::BOTS, $agent)) {
                return;
            }

            $today = now(config('auracinema.stats_timezone'))->toDateString();

            $movie = $request->route('movie');
            $movieId = $movie instanceof Movie ? $movie->id : (is_numeric($movie) ? (int) $movie : null);

            PageView::create([
                'viewed_on' => $today,
                'visitor' => substr(hash_hmac('sha256', $request->ip().'|'.$agent.'|'.$today, (string) config('app.key')), 0, 32),
                'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 255),
                'movie_id' => $movieId,
                'referrer_host' => $this->referrerHost($request),
                'device' => preg_match('/Mobi|Android|iPhone|iPad/i', $agent) ? 'mobile' : 'desktop',
            ]);

            // Housekeeping: now and then, drop rows older than 180 days.
            if (random_int(1, 200) === 1) {
                PageView::where('viewed_on', '<', now(config('auracinema.stats_timezone'))->subDays(180)->toDateString())->delete();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Where the visitor came from (e.g. google.com), or null for direct / same-site visits. */
    private function referrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = preg_replace('/^www\./', '', mb_strtolower($host));
        $own = [
            preg_replace('/^www\./', '', mb_strtolower($request->getHost())),
            preg_replace('/^www\./', '', mb_strtolower((string) parse_url((string) config('auracinema.site_url'), PHP_URL_HOST))),
        ];

        return in_array($host, $own, true) ? null : mb_substr($host, 0, 100);
    }
}
