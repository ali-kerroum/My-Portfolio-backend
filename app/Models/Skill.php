<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = [
        'category',
        'icon',
        'accent',
        'items',
        'sort_order',
    ];

    protected $casts = [
        'items' => 'array',
    ];
}
