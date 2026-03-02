<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SantunanActivity extends Model
{
    protected $table = 'santunan_activities';

    protected $fillable = [
        'nama_kegiatan',
        'deskripsi',
        'tanggal_mulai',
        'tanggal_selesai',
        'target_donasi',
        'status',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'target_donasi' => 'decimal:2',
    ];

    public function donations(): HasMany
    {
        return $this->hasMany(SantunanDonation::class, 'activity_id');
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(Santunan::class, 'activity_id');
    }
}
