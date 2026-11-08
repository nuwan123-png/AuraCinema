<?php

namespace App\Support;

class EmbedUrl
{
    /**
     * Normalize and validate an embed URL or iframe snippet.
     * Returns a valid iframe embed src URL or null if invalid/unsupported.
     */
    public static function normalize(?string $input): ?string
    {
        if (empty($input)) {
            return null;
        }

        $input = trim($input);

        // Extract src if full iframe is pasted
        if (preg_match('/<iframe[^>]+src=["']([^"']+)["']/i', $input, $matches)) {
            $input = $matches[1];
        }

        // Ensure scheme exists
        if (!preg_match('~^(?:f|ht)tps?://~i', $input)) {
            $input = 'https://' . ltrim($input, '/');
        }

        $parsed = parse_url($input);
        if (!$parsed || !isset($parsed['host'])) {
            return null;
        }

        $host = strtolower($parsed['host']);
        $path = $parsed['path'] ?? '';
        parse_str($parsed['query'] ?? '', $query);

        // 1. YouTube
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'])) {
            if (isset($query['v'])) {
                return 'https://www.youtube-nocookie.com/embed/' . $query['v'];
            }
            if (preg_match('~/embed/([a-zA-Z0-9_-]+)~', $path, $m)) {
                return 'https://www.youtube-nocookie.com/embed/' . $m[1];
            }
            if (preg_match('~/shorts/([a-zA-Z0-9_-]+)~', $path, $m)) {
                return 'https://www.youtube-nocookie.com/embed/' . $m[1];
            }
        }
        if ($host === 'youtu.be') {
            $id = trim($path, '/');
            if (!empty($id)) {
                return 'https://www.youtube-nocookie.com/embed/' . $id;
            }
        }

        // 2. Vimeo
        if (in_array($host, ['vimeo.com', 'www.vimeo.com'])) {
            if (preg_match('~/([0-9]+)~', $path, $m)) {
                return 'https://player.vimeo.com/video/' . $m[1];
            }
        }
        if ($host === 'player.vimeo.com') {
            return $input;
        }

        // 3. Bunny Stream
        if ($host === 'iframe.mediadelivery.net') {
            return $input;
        }

        // 4. Cloudflare Stream
        if (in_array($host, ['cloudflarestream.com', 'watch.cloudflarestream.com'])) {
            if (preg_match('~/([a-zA-Z0-9]+)/iframe~', $path)) {
                return $input;
            }
            $id = trim($path, '/');
            if (!empty($id)) {
                return 'https://iframe.videodelivery.net/' . $id;
            }
        }
        if ($host === 'iframe.videodelivery.net') {
            return $input;
        }

        // 5. Streamtape
        if (in_array($host, ['streamtape.com', 'www.streamtape.com', 'streamtape.to', 'streamtape.net'])) {
            // Check for /e/ID (embed) or /v/ID (view/download)
            if (preg_match('~/(?:e|v)/([a-zA-Z0-9_-]+)~', $path, $m)) {
                return 'https://streamtape.com/e/' . $m[1];
            }
        }

        return null;
    }
}
