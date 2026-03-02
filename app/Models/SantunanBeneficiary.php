<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SantunanBeneficiary extends Model
{
    protected $table = 'santunan_beneficiaries';

    protected $fillable = [
        'nama_lengkap',
        'jenis', // yatim, dhuafa
        'rt_id',
        'alamat',
        'is_active',
        'keterangan',
        'data_tambahan', // json
    ];

    protected $casts = [
        'data_tambahan' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeYatim($query)
    {
        return $query->where('jenis', 'yatim');
    }

    public function scopeDhuafa($query)
    {
        return $query->where('jenis', 'dhuafa');
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(RT::class, 'rt_id');
    }
}
