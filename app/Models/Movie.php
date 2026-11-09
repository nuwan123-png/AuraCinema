<?php

namespace App\Models;

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
    ];

    /** Episodes of a series, in order (episode 1 first). */
    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('number');
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
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    public function getTrailerFileUrlAttribute(): ?string
    {
        return $this->trailer_url ? asset('storage/'.$this->trailer_url) : null;
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
            'image' => $this->image ? $base.'/storage/'.$this->image : null,
            'url' => $base.'/movies/'.$this->id,
            'dateCreated' => $this->created_at?->toDateString(),
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    }
}