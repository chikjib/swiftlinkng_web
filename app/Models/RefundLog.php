<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ref',
        'order_id',
        'credited_amount',
        'user_id',
    ];

}
