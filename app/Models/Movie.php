<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'trailer_url',
        'stream_url',
    ];

    public function getPosterUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    public function getTrailerFileUrlAttribute(): ?string
    {
        return $this->trailer_url ? asset('storage/'.$this->trailer_url) : null;
    }
}
