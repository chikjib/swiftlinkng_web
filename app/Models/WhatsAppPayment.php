<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppPayment extends Model
{
    protected $connection = 'whatsapp_bot';
    protected $table = 'payments';
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'date_paid' => 'datetime',
    ];
}
