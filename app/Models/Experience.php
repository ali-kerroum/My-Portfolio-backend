<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    protected $fillable = [
        'role',
        'period',
        'organization',
        'icon',
        'accent',
        'points',
        'sort_order',
    ];

    protected $casts = [
        'points' => 'array',
    ];
}
