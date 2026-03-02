<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'user_id',
        'rt_id',
        'nik',
        'nama_lengkap',
        'panggilan',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat_ktp',
        'alamat_domisili',
        'no_wa',
        'email',
        'foto_url',
        'skills',
        'status_hidup'
    ];

    protected $casts = [
        'skills' => 'array',
        'tanggal_lahir' => 'date',
    ];

    public function rt()
    {
        return $this->belongsTo(RT::class, 'rt_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}
