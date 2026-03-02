<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignatureRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_name',
        'category_filter',
        'rt_filter',
        'left_signer_id',
        'right_signer_id',
        'priority'
    ];

    public function leftSigner()
    {
        return $this->belongsTo(Signer::class, 'left_signer_id');
    }

    public function rightSigner()
    {
        return $this->belongsTo(Signer::class, 'right_signer_id');
    }
}
