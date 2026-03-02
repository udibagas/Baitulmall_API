<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'seller_name',
        'seller_phone',
        'category',
        'image_url',
        'rt_id',
        'maps_link',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function rt(): BelongsTo
    {
        return $this->belongsTo(RT::class, 'rt_id');
    }
}
