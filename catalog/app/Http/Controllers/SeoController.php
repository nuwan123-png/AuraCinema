<?php

namespace App\Http\Controllers;

use App\Models\Movie;

class SeoController extends Controller
{
    public function sitemap()
    {
        $base = rtrim((string) config('auracinema.site_url'), '/');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        $xml .= '  <url><loc>'.e($base.'/').'</loc></url>'."\n";

        foreach (Movie::select(['id', 'updated_at'])->orderBy('id')->cursor() as $movie) {
            $lastmod = $movie->updated_at ? '<lastmod>'.$movie->updated_at->toAtomString().'</lastmod>' : '';
            $xml .= '  <url><loc>'.e($base.'/movies/'.$movie->id).'</loc>'.$lastmod.'</url>'."\n";
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots()
    {
        $base = rtrim((string) config('auracinema.site_url'), '/');

        $body = "User-agent: *\nAllow: /\nDisallow: /movies/create\nDisallow: /movies/*/edit\nDisallow: /*?q=\nDisallow: /*?type=\nDisallow: /*?genre=\nDisallow: /*?year=\nDisallow: /suggestions\nDisallow: /requests\n\nSitemap: {$base}/sitemap.xml\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
