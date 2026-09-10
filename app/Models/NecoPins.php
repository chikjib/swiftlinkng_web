<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NecoPins extends Model
{
    use HasFactory;
    protected $fillable = [
        "pin_no",
        "used",
        "variation_code",
    ];
}
