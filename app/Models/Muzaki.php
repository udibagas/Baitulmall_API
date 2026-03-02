<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Muzaki extends Model
{
    protected $table = 'muzaki';

    protected $fillable = [
        'rt_id',
        'nama',
        'no_hp',
        'jumlah_jiwa',
        'jumlah_beras_kg',
        'status_bayar',
        'receipt_path',
        'tahun',
        'tanggal_bayar',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'jumlah_jiwa' => 'integer',
        'jumlah_beras_kg' => 'decimal:2',
        'tahun' => 'integer',
        'tanggal_bayar' => 'date',
    ];

    /**
     * Get the RT that owns this Muzaki
     */
    public function rt(): BelongsTo
    {
        return $this->belongsTo(RT::class, 'rt_id');
    }

    /**
     * Get all Zakat Fitrah payments for this Muzaki
     */
    public function zakatFitrah(): HasMany
    {
        return $this->hasMany(ZakatFitrah::class, 'muzaki_id');
    }

    /**
     * Check if this Muzaki has paid (lunas)
     */
    public function isPaid(): bool
    {
        return $this->status_bayar === 'lunas';
    }

    /**
     * Get total paid amount
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->zakatFitrah()->sum('jumlah_kg');
    }

    /**
     * Get Zakat Calculation Histories
     */
    public function zakatCalculationHistories(): HasMany
    {
        return $this->hasMany(ZakatCalculationHistory::class);
    }

    /**
     * Get the user who created this Muzaki
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this Muzaki
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
