<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppTransactionAdminLog extends Model
{
    protected $fillable = [
        'admin_user_id',
        'bot_order_id',
        'old_status',
        'new_status',
        'note',
    ];
}
