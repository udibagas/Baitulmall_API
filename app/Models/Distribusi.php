<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Distribusi extends Model
{
    protected $table = 'distribusi';

    protected $fillable = [
        'asnaf_id',
        'kategori_asnaf',
        'jumlah_kg',
        'jumlah_rupiah',
        'tanggal',
        'tahun',
        'status',
        'distributed_by',
        'catatan',
    ];

    protected $casts = [
        'jumlah_kg' => 'decimal:2',
        'jumlah_rupiah' => 'decimal:2',
        'tahun' => 'integer',
        'tanggal' => 'date',
    ];

    /**
     * Get the Asnaf who received this distribution
     */
    public function asnaf(): BelongsTo
    {
        return $this->belongsTo(Asnaf::class, 'asnaf_id');
    }

    /**
     * Mark distribution as distributed
     */
    public function markAsDistributed(string $distributedBy): bool
    {
        return $this->update([
            'status' => 'distributed',
            'distributed_by' => $distributedBy,
        ]);
    }

    /**
     * Mark distribution as verified
     */
    public function markAsVerified(): bool
    {
        return $this->update(['status' => 'verified']);
    }

    /**
     * Check if distribution is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'verified';
    }
}
