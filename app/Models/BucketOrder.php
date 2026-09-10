<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Spatie\WebhookServer\WebhookCall;


class BucketOrder extends Model
{
    use HasFactory;

    protected $table = 'bucket_orders';

    protected $fillable = [
        'ref',
        'user_id',
        'bucket_id',
        'plan',
        'quantity',
        'amount',
        'total',
        'subtotal',
        'description',
        'status',
        'data_size',
        'phone',
        'bal',
        'prev_bal',
        'bucket_bal',
        'prev_bucket_bal',
        'channel',
        'response',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bucket()
    {
        return $this->belongsTo(Bucket::class);
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
        static::updated(function (BucketOrder $bucketOrder){
            $user = User::find($bucketOrder->user_id);
            $url = $user->webhook_url;

            if(!is_null($url)){
                WebhookCall::create()
                    ->url($url)
                    ->payload($bucketOrder->toArray())
                    ->throwExceptionOnFailure()
                    ->useSecret('sign-using-this-secret')
                    ->dispatch();
            }


        });
    }
}
