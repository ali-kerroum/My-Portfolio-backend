<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'number',
        'title',
        'description',
        'items',
        'icon',
        'sort_order',
    ];

    protected $casts = [
        'items' => 'array',
    ];
}
