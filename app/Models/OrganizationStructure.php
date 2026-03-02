<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationStructure extends Model
{
    use HasFactory;

    protected $table = 'organization_structures';

    protected $fillable = [
        'parent_id',
        'kode_struktur',
        'nama_struktur',
        'deskripsi',
        'lokasi',
        'status',
        'rundown',
        'anggaran',
        'pemasukan',
        'checklist',
        'panitia',
        'tipe',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rundown' => 'array',
        'anggaran' => 'array',
        'pemasukan' => 'array',
        'checklist' => 'array',
        'panitia' => 'array',
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime'
    ];

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'structure_id');
    }
}
