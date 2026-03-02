<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZakatProduktifMonitoring extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'zakat_produktif_monitoring';

    protected $fillable = [
        'zakat_produktif_id',
        'tanggal_laporan',
        'omzet',
        'laba',
        'catatan'
    ];

    protected $casts = [
        'tanggal_laporan' => 'date',
        'omzet' => 'decimal:2',
        'laba' => 'decimal:2',
    ];

    public function zakatProduktif(): BelongsTo
    {
        return $this->belongsTo(ZakatProduktif::class, 'zakat_produktif_id');
    }
}
