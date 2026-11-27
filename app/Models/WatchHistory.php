<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchHistory extends Model
{
    protected $fillable = [
        'user_id',
        'movie_id',
        'episode_id',
        'title',
        'poster_url',
        'type',
        'position_seconds',
        'duration_seconds',
    ];
}