<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppCustomer extends Model
{
    protected $connection = 'whatsapp_bot';
    protected $table = 'customers';
    protected $guarded = [];
}
