<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commissions extends Model
{
    use HasFactory;
    protected $table = 'commissions';

    protected $fillable = [
        'user_id', 'product_id',
        'farmer_id',
        'total', 'commission',
        'com_percent'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }


    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
