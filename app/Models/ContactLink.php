<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactLink extends Model
{
    protected $fillable = [
        'label',
        'href',
        'icon_svg',
        'sort_order',
    ];
}
