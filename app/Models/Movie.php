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
