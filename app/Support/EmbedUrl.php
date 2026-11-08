<?php

namespace App\Support;

/**
 * Turns whatever the admin pasted into a safe player URL, or null if it is not an allowed one.
 *
 * Accepted input: a player/embed URL, a normal YouTube link (watch?v=… or youtu.be/…), or a whole
 * <iframe …> snippet (only its src is used). Only https links on the hosts in
 * config('auracinema.embed_hosts') are ever allowed, so a pasted snippet can never load an
 * arbitrary site inside your pages.
 */
class EmbedUrl
{
    public static function normalize(?string $input): ?string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }

        // A pasted <iframe ... src="..."> snippet: keep only the src.
        if (stripos($input, '<iframe') !== false) {
            if (! preg_match('/\bsrc\s*=\s*(["\'])(.*?)\1/is', $input, $m)) {
                return null;
            }
            $input = trim(html_entity_decode($m[2]));
        }

        // Streamtape links (e.g., https://streamtape.com/v/... or /e/...) -> convert to embed URL
        if (preg_match('#^https?://(?:www\.)?streamtape\.(?:com|to|net)/(?:v|e)/([a-zA-Z0-9_-]+)#i', $input, $matches)) {
            return 'https://streamtape.com/e/' . $matches[1];
        }

        $parts = parse_url($input);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['port'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        // Normal YouTube links -> the embeddable form.
        if ($host === 'youtu.be' && preg_match('#^/([\w-]{6,20})$#', $path, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            if ($path === '/watch' && isset($query['v']) && is_string($query['v']) && preg_match('/^[\w-]{6,20}$/', $query['v'])) {
                return 'https://www.youtube.com/embed/'.$query['v'];
            }
            if (preg_match('#^/embed/[\w-]{6,20}$#', $path)) {
                return 'https://www.youtube.com'.$path;
            }

            return null;
        }
        if ($host === 'www.youtube-nocookie.com') {
            return preg_match('#^/embed/[\w-]{6,20}$#', $path) ? 'https://www.youtube-nocookie.com'.$path : null;
        }

        if (! self::hostAllowed($host)) {
            return null;
        }

        // Vimeo / Bunny / Cloudflare: keep their own player URL (path + query) as given.
        $url = 'https://'.$host.$path;
        if (($parts['query'] ?? '') !== '') {
            $url .= '?'.$parts['query'];
        }

        return strlen($url) <= 500 ? $url : null;
    }

    public static function hostAllowed(string $host): bool
    {
        foreach ((array) config('auracinema.embed_hosts') as $allowed) {
            if ($host === $allowed || (str_starts_with($allowed, '*.') && str_ends_with($host, substr($allowed, 1)))) {
                return true;
            }
        }

        return false;
    }
}