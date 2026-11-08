<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'release_year',
        'duration_minutes',
        'poster_path',
        'trailer_path',
        'embed_url',
    ];

    protected $casts = [
        'release_year' => 'integer',
        'duration_minutes' => 'integer',
    ];
}
