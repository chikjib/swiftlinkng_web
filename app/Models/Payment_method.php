<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment_method extends Model
{
    protected $table = 'payment_method';

    use HasFactory;


    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
