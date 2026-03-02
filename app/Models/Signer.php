<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signer extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_pejabat',
        'jabatan',
        'nip',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];
}
