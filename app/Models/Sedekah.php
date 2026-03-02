<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sedekah extends Model
{
    protected $table = 'sedekah';

    protected $fillable = [
        'amil_id',
        'rt_id',
        'jumlah',
        'jenis',
        'tujuan',
        'tanggal',
        'tahun',
        'nama_donatur',
        'no_hp_donatur',
        'receipt_path',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tahun' => 'integer',
        'tanggal' => 'date',
    ];

    public function rt(): BelongsTo
    {
        return $this->belongsTo(RT::class, 'rt_id');
    }

    public function amil(): BelongsTo
    {
        return $this->belongsTo(Asnaf::class, 'amil_id');
    }
}
