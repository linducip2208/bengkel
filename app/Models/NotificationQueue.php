<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationQueue extends Model
{
    protected $table = 'notification_queue';

    protected $fillable = ['channel', 'recipient', 'message', 'metadata', 'status', 'error', 'sent_at'];

    protected $casts = ['metadata' => 'array', 'sent_at' => 'datetime'];
}
