<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SantunanDonation extends Model
{
    protected $table = 'santunan_donations';

    protected $fillable = [
        'nama_donatur',
        'jumlah',
        'tanggal',
        'tahun',
        'keterangan',
        'user_id'
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tahun' => 'integer',
        'tanggal' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
