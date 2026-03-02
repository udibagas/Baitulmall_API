<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Correspondence extends Model
{
    protected $fillable = [
        'nomor_surat',
        'jenis_surat',
        'perihal',
        'tujuan',
        'isi_surat',
        'tanggal_surat',
        'status'
    ];

    protected $casts = [
        'tanggal_surat' => 'date'
    ];
}
