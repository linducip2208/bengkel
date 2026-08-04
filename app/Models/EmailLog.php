<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailLog extends Model
{
    use SoftDeletes;

    protected $table = 'email_logs';

    protected $fillable = ['to', 'subject', 'body', 'status', 'error_message'];
}
