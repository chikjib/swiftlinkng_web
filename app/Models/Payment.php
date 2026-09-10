<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        //  'product_id',
        'order_ref',
        'payment_method_id',
        'amount',
        'response',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order_ref()
    {
        return $this->belongsTo(Order::class, 'ref');
    }

    public function payment_method()
    {
        return $this->belongsTo(Payment_method::class);
    }
}
