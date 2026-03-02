<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpactStory extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'beneficiary_name_masked',
        'category',
        'image_path',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];
}
