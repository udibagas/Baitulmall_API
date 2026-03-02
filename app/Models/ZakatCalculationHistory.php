<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZakatCalculationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'muzaki_id',
        'zakat_type',
        'total_assets',
        'deductible_debt',
        'nisab_threshold',
        'zakat_rates_percent',
        'calculated_amount',
        'is_payable',
        'haul_met',
        'calculation_date',
        'details',
    ];

    protected $casts = [
        'total_assets' => 'decimal:2',
        'deductible_debt' => 'decimal:2',
        'nisab_threshold' => 'decimal:2',
        'zakat_rates_percent' => 'decimal:2',
        'calculated_amount' => 'decimal:2',
        'is_payable' => 'boolean',
        'haul_met' => 'boolean',
        'calculation_date' => 'date',
        'details' => 'array',
    ];

    public function muzaki(): BelongsTo
    {
        return $this->belongsTo(Muzaki::class);
    }
}
