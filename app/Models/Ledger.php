<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    use HasFactory;
    protected $table = 'Ledger';


    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'payment_method_id',
        'amount',
    ];
    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Product()
    {
        return $this->belongsTo(Product::class);
    }

    public function Order()
    {
        return $this->belongsTo(Order::class);
    }

    public function Payment_method()
    {
        return $this->belongsTo(Payment_method::class);
    }
}
