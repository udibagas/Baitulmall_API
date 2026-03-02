<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'recipient_phone',
        'message',
        'status',
        'provider',
        'response_data'
    ];
}
