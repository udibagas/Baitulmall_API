<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetLoan extends Model
{
    protected $fillable = [
        'asset_id',
        'borrower_name',
        'borrower_phone',
        'loan_date',
        'expected_return_date',
        'actual_return_date',
        'status',
        'notes',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
