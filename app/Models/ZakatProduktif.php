<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZakatProduktif extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'zakat_produktif';

    protected $fillable = [
        'asnaf_id',
        'nama_usaha',
        'modal_awal',
        'keterangan',
        'tanggal_mulai',
        'status'
    ];

    protected $casts = [
        'modal_awal' => 'decimal:2',
        'tanggal_mulai' => 'date',
    ];

    public function asnaf(): BelongsTo
    {
        return $this->belongsTo(Asnaf::class, 'asnaf_id');
    }

    public function monitoring(): HasMany
    {
        return $this->hasMany(ZakatProduktifMonitoring::class, 'zakat_produktif_id');
    }
}
