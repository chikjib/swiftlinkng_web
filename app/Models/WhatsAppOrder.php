<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppOrder extends Model
{
    protected $connection = 'whatsapp_bot';
    protected $table = 'orders';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    protected $appends = [
        'plan_name',
        'provider_name',
    ];

    protected $hidden = [
        'payload',
        'plan',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'wallet_amount' => 'decimal:2',
        'external_amount' => 'decimal:2',
        'payment_expires_at' => 'datetime',
        'payload' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(WhatsAppCustomer::class, 'customer_id');
    }

    public function payment()
    {
        return $this->hasOne(WhatsAppPayment::class, 'order_id');
    }

    public function plan()
    {
        return $this->belongsTo(WhatsAppPlan::class, 'plan_id');
    }

    public function getPlanNameAttribute()
    {
        $payload = is_array($this->payload) ? $this->payload : [];

        foreach ([
            'plan_title',
            'plan_name',
            'package_name',
            'bouquet_name',
            'variation_name',
        ] as $key) {
            $name = trim((string) data_get($payload, $key, ''));

            if ($name !== '') {
                return $name;
            }
        }

        if ($this->service_type === 'data' && $this->relationLoaded('plan')) {
            return $this->plan ? $this->plan->plan : null;
        }

        return null;
    }

    public function getProviderNameAttribute()
    {
        $payload = is_array($this->payload) ? $this->payload : [];

        foreach (['network_title', 'provider_title'] as $key) {
            $name = trim((string) data_get($payload, $key, ''));

            if ($name !== '') {
                return $name;
            }
        }

        if (
            $this->service_type === 'data' &&
            $this->relationLoaded('plan') &&
            $this->plan &&
            $this->plan->relationLoaded('provider') &&
            $this->plan->provider
        ) {
            return $this->plan->provider->title;
        }

        $network = trim((string) $this->network);

        return $network !== '' && ! is_numeric($network)
            ? $network
            : null;
    }
}
