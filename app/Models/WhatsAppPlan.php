<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppPlan extends Model
{
    protected $connection = 'whatsapp_bot';
    protected $table = 'plans';
    protected $guarded = [];

    public function provider()
    {
        return $this->belongsTo(WhatsAppProvider::class, 'provider_id');
    }
}
