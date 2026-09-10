<?php

namespace App\Models;

use App\Services\RewardService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookServer\WebhookCall;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'ref',
        'settlement_id',
        'user_id',
        'category_id',
        'subcategory_id',
        'price',
        'quantity',
        'total',
        'subtotal',
        'description',
        'status',
        'phone',
        'iuc',
        'meter',
        'bal',
        'prev_bal',
        'channel'

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function payment_method()
    {
        return $this->belongsTo(Payment_method::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
    
    public static function boot()
    {
        parent::boot();

        /*
         * Register reward handling before outbound customer webhooks so a
         * webhook delivery error cannot prevent an earned reward.
         */
        static::updated(function (Order $order) {
            if ($order->wasChanged('status') && (int) $order->status === 1) {
                self::processReferralReward($order);
            }
        });

        static::created(function (Order $order) {
            if ((int) $order->status === 1) {
                self::processReferralReward($order);
            }
        });

        static::updated(function (Order $order){ 
            $user = User::find($order->user_id);
            $url = $user ? $user->webhook_url : null;

            if(!is_null($url)){
                WebhookCall::create()
                    ->url($url)
                    ->payload($order->toArray())
                    ->throwExceptionOnFailure()
                    ->useSecret('sign-using-this-secret')
                    ->dispatch();
            }

        });
    }

    private static function processReferralReward(Order $order)
    {
        try {
            RewardService::processSuccessfulOrder($order);
        } catch (\Throwable $exception) {
            /*
             * Reward failures must never turn a delivered customer
             * transaction into a failed purchase.
             */
            Log::error('Referral reward processing failed after order success', [
                'order_id' => $order->id,
                'order_ref' => $order->ref,
                'user_id' => $order->user_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
    
    // protected static function booted()
    // {
    //     static::addGlobalScope('user', function ($query) {
    //         if (auth()->check()) {
    //             $query->where('user_id', auth()->id());
    //         }
    //     });
    // }
}
