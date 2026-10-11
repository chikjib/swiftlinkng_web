<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsRecipientList extends Model
{
    protected $fillable = ['name', 'recipients'];
    protected $casts = ['recipients' => 'array'];
    protected $hidden = ['user_id'];
}
