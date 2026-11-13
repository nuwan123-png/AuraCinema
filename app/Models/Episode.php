<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Episode extends Model
{
    protected $fillable = ['movie_id', 'number', 'url', 'subtitle_url', 'download_url'];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}
