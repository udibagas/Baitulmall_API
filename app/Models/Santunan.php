<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Santunan extends Model
{
    protected $table = 'santunan';

    protected $fillable = [
        'nama_anak',
        'beneficiary_id',
        'rt_id',
        'besaran',
        'status_penerimaan',
        'tanggal_distribusi',
        'tahun',
        'activity_id',
        'kategori',
    ];

    protected $casts = [
        'besaran' => 'decimal:2',
        'tahun' => 'integer',
        'tanggal_distribusi' => 'date',
    ];

    public function rt(): BelongsTo
    {
        return $this->belongsTo(RT::class, 'rt_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(SantunanActivity::class, 'activity_id');
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(SantunanBeneficiary::class, 'beneficiary_id');
    }
}
