<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgendaPost extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'schedule_date' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(OrganizationStructure::class, 'event_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'agenda_id');
    }
}
