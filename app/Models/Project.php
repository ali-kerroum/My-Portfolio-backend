<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'title',
        'description',
        'technologies',
        'image',
        'category',
        'link',
        'github',
        'videos',
        'images',
        'stats',
        'skills',
        'problem',
        'solution',
        'benefits',
        'sections',
        'sort_order',
    ];

    protected $casts = [
        'technologies' => 'array',
        'videos' => 'array',
        'images' => 'array',
        'stats' => 'array',
        'skills' => 'array',
        'solution' => 'array',
        'benefits' => 'array',
        'sections' => 'array',
    ];
}
