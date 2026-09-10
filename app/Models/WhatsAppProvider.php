<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppProvider extends Model
{
    protected $connection = 'whatsapp_bot';
    protected $table = 'providers';
    protected $guarded = [];
}
