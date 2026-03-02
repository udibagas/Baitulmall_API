<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoldPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_per_gram',
        'currency',
        'source',
    ];

    protected $casts = [
        'price_per_gram' => 'decimal:2',
    ];
}
