<?php

namespace App\Models;

use App\Support\MediaStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'trailer_url',
        'stream_url',
        'embed_url',
        'type',
        'genre',
        'year',
        'language',
        'tags',
        'download_480p',
        'download_720p',
        'download_1080p',
        'subtitle_url',
        'gallery',
        'cast_members',
    ];

    protected $casts = [
        'gallery' => 'array',
        'cast_members' => 'array',
    ];

    /** Episodes of a series, in order (episode 1 first). */
    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('number');
    }

    /** Cached result of missingFields() (admin-only helper). */
    private ?array $missingFieldsCache = null;

    /**
     * Admin helper: names of the form fields that are still empty.
     * To stop flagging a field, just delete (or comment out) its line below.
     */
    public function missingFields(): array
    {
        if ($this->missingFieldsCache !== null) {
            return $this->missingFieldsCache;
        }

        // Plain movies loaded with withCount('episodes') = 0 need no extra query.
        $count = $this->attributes['episodes_count'] ?? null;
        $episodes = ($count !== null && (int) $count === 0) ? collect() : $this->episodes;
        $hasEpisodes = $episodes->isNotEmpty();

        $missing = [];

        if (blank($this->description)) {
            $missing[] = 'Description';
        }
        if (blank($this->image)) {
            $missing[] = 'Poster';
        }
        if (blank($this->trailer_url)) {
            $missing[] = 'Trailer';
        }
        if (blank($this->tags)) {
            $missing[] = 'Keywords';
        }

        // Only Movie specific requirements
        if (! $hasEpisodes) {
            // Watch now Embed URL
            if (blank($this->embed_url)) {
                $missing[] = 'Watch now link (or episodes)';
            }
            
            // Movie Download Links (One link is enough)
            $hasAnyDownloadLink = !blank($this->download_480p) || 
                                  !blank($this->download_720p) || 
                                  !blank($this->download_1080p);
            
            if (!$hasAnyDownloadLink) {
                $missing[] = 'At least one download link (480p, 720p, or 1080p)';
            }
            
            // Movie Subtitle Link
            if (blank($this->subtitle_url)) {
                $missing[] = 'Subtitle link';
            }
        } 
        // Series specific Subtitle requirements
        else {
            $without = $episodes->filter(fn ($e) => blank($e->subtitle_url))->pluck('number')->sort()->values();
            if ($without->isNotEmpty()) {
                $missing[] = $without->count() > 6
                    ? 'Subtitles for '.$without->count().' episodes'
                    : 'Subtitle for Ep '.$without->implode(', ');
            }
        }

        return $this->missingFieldsCache = $missing;
    }

    public function isIncomplete(): bool
    {
        return $this->missingFields() !== [];
    }

    public function getTypeLabelAttribute(): string
    {
        return (string) config('auracinema.types.'.$this->type, 'Movie');
    }

    /** Keywords as a plain list, e.g. ['hindi', 'romantic']. Always clean & lowercase. */
    public function getTagListAttribute(): array
    {
        if (empty($this->tags)) {
            return [];
        }

        return collect(explode(',', (string) $this->tags))
            ->map(fn ($t) => self::normalizeTag($t))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Turn what the admin typed ("Hindi, romantic ,2026") into the stored form ",hindi,romantic,2026,". */
    public static function normalizeTags(?string $input): ?string
    {
        $tags = collect(explode(',', (string) $input))
            ->map(fn ($t) => self::normalizeTag($t))
            ->filter()
            ->unique()
            ->take(12)
            ->values();

        return $tags->isEmpty() ? null : ','.$tags->implode(',').',';
    }

    public static function normalizeTag(?string $tag): string
    {
        $tag = mb_strtolower(trim((string) $tag));
        $tag = preg_replace('/[^\p{L}\p{N} \-]+/u', '', $tag) ?? '';

        return mb_substr(trim(preg_replace('/\s+/', ' ', $tag) ?? ''), 0, 30);
    }

    public function getPosterUrlAttribute(): ?string
    {
        return MediaStore::url($this->image);
    }

    /** Added within the last 14 days: shows the NEW badge on the poster card. */
    public function isNew(): bool
    {
        return $this->created_at !== null && $this->created_at->gt(now()->subDays(14));
    }

    public function getTrailerFileUrlAttribute(): ?string
    {
        return MediaStore::url($this->trailer_url);
    }

    /** Gallery pictures as [['path' => ..., 'url' => ...], ...] ready for the page. */
    public function getGalleryItemsAttribute(): array
    {
        return collect($this->gallery ?: [])
            ->filter(fn ($p) => is_string($p) && $p !== '')
            ->map(fn ($p) => ['path' => $p, 'url' => (string) MediaStore::url($p)])
            ->values()
            ->all();
    }

    /** Cast as [['name','role','photo' => url|null], ...] ready for the page. */
    public function getCastListAttribute(): array
    {
        return collect($this->cast_members ?: [])
            ->filter(fn ($c) => is_array($c) && filled($c['name'] ?? null))
            ->map(fn ($c) => [
                'name' => (string) $c['name'],
                'role' => (string) ($c['role'] ?? ''),
                'photo' => ! empty($c['photo']) ? MediaStore::url($c['photo']) : null,
            ])
            ->values()
            ->all();
    }

    /** schema.org Movie data for Google (JSON, safe to print inside a <script type="application/ld+json"> tag). */
    public function getJsonLdAttribute(): string
    {
        $base = rtrim((string) config('auracinema.site_url'), '/');

        return json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => $this->type === 'movie' ? 'Movie' : 'TVSeries',
            'name' => $this->title,
            'description' => $this->description,
            'image' => $this->image ? $this->absoluteMediaUrl($this->image, $base) : null,
            'url' => $base.'/movies/'.$this->id,
            'dateCreated' => $this->created_at?->toDateString(),
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    /** Poster URL for JSON-LD: R2 address when the file lives there, else the site's own /storage path. */
    private function absoluteMediaUrl(string $path, string $base): string
    {
        $url = (string) MediaStore::url($path);

        return str_contains($url, '/storage/') ? $base.'/storage/'.$path : $url;
    }
}