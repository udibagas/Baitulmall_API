<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrowdfundingCampaign extends Model
{
    protected $fillable = [
        'title',
        'description',
        'target_amount',
        'current_amount',
        'start_date',
        'end_date',
        'image_url',
        'status',
        'slug'
    ];
}
