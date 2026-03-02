<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'name',
        'code',
        'category',
        'condition',
        'acquisition_date',
        'value',
        'is_lendable',
    ];
}
