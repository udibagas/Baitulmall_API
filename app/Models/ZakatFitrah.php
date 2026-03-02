<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZakatFitrah extends Model
{
    protected $table = 'zakat_fitrah';

    protected $fillable = [
        'muzaki_id',
        'rt_id',
        'jumlah_jiwa',
        'jumlah_kg',
        'jumlah_rupiah',
        'jenis_bayar',
        'tanggal',
        'tahun',
        'catatan',
    ];

    protected $casts = [
        'jumlah_jiwa' => 'integer',
        'jumlah_kg' => 'decimal:2',
        'jumlah_rupiah' => 'decimal:2',
        'tahun' => 'integer',
        'tanggal' => 'date',
    ];

    /**
     * Get the Muzaki who made this payment
     */
    public function muzaki(): BelongsTo
    {
        return $this->belongsTo(Muzaki::class, 'muzaki_id');
    }

    /**
     * Get the RT where this was collected
     */
    public function rt(): BelongsTo
    {
        return $this->belongsTo(RT::class, 'rt_id');
    }
}
